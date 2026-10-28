param([switch]$Preview)
. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
$ErrorActionPreference='Stop'
$release=Join-Path $script:V4Root 'release'; $stage=Join-Path $script:V4Root 'runtime\package-stage'
if(Test-Path $release){Remove-Item -LiteralPath $release -Recurse -Force}; New-Item -ItemType Directory -Force $release,$stage | Out-Null
if(!$Preview){$prodEnv=Join-Path $script:Backend '.env.infinityfree';if(!(Test-Path -LiteralPath $prodEnv)){throw 'Missing backend/.env.infinityfree. Refusing production package build.'};$tracked=(git ls-files -- backend/.env.infinityfree);if($tracked){throw 'backend/.env.infinityfree is tracked; refusing production package build.'};$values=@{};foreach($line in Get-Content -LiteralPath $prodEnv){if($line -match '^\s*([^#=]+)=(.*)$'){$values[$matches[1].Trim()]=$matches[2].Trim().Trim('"',"'")}};foreach($key in 'APP_ENV','APP_ALLOWED_ORIGIN','DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASSWORD','BOOTSTRAP_TOKEN'){if([string]::IsNullOrWhiteSpace($values[$key])){throw "Missing required production configuration: $key"}};if($values.APP_ENV -ne 'production' -or $values.APP_ALLOWED_ORIGIN -ne 'https://player.dnd-portal.com'){throw 'Production configuration has an invalid environment or origin.'};$prod=$values}else{$prod=@{}}
Push-Location $script:Player; pnpm build | Out-Host; Pop-Location
Copy-Item (Join-Path $script:Player 'build\*') $stage -Recurse -Force
New-Item -ItemType Directory -Force (Join-Path $stage 'server\public'),(Join-Path $stage 'server\src') | Out-Null
Copy-Item (Join-Path $script:Backend 'public\index.php') (Join-Path $stage 'server\public\index.php')
Copy-Item (Join-Path $script:Backend 'src\*') (Join-Path $stage 'server\src') -Recurse -Force
@"
APP_ENV=production
APP_ALLOWED_ORIGIN=$(if($Preview){'preview-injected'}else{$prod.APP_ALLOWED_ORIGIN})
DB_HOST=$(if($Preview){'preview-injected'}else{$prod.DB_HOST})
DB_PORT=$(if($Preview){'3306'}else{$prod.DB_PORT})
DB_NAME=$(if($Preview){'preview-injected'}else{$prod.DB_NAME})
DB_USER=$(if($Preview){'preview-injected'}else{$prod.DB_USER})
DB_PASSWORD=$(if($Preview){'preview-injected'}else{$prod.DB_PASSWORD})
BOOTSTRAP_TOKEN=$(if($Preview){'preview-injected'}else{$prod.BOOTSTRAP_TOKEN})
"@ | Set-Content (Join-Path $stage 'server\.env') -NoNewline
@'
Options -Indexes
RewriteEngine On
RewriteRule ^(?:\.env|server(?:/|$)|backend(?:/|$)|scripts(?:/|$)|development(?:/|$)|release(?:/|$)|runtime(?:/|$)|node(?:\x5f)modules(?:/|$)|tests?(?:/|$)|.*\.(?:sql|log|bak|zip|ps1|sh|md)$) - [R=404,L,NC]
RewriteCond %{THE_REQUEST} \s/+server(?:[/?\s]) [NC]
RewriteRule ^server(?:/|$) - [R=404,L]
RewriteRule ^api(?:/.*)?$ server/public/index.php [END,QSA]
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^ index.html [L]
'@ | Set-Content (Join-Path $stage '.htaccess')
@'
Options -Indexes
Require all granted
<FilesMatch "^\.env$">
  Require all denied
</FilesMatch>
'@ | Set-Content (Join-Path $stage 'server\.htaccess')
$forbidden='localhost','127.0.0.1','dnd.toonvb.com','dnd.lovestoblog.com','dnd_portal_v4_development','dnd_portal_v4_preview','node_modules','V4-TYPE-A-INVITE','V4-TYPE-B-INVITE','v4-player','v4-admin','TESTUSER_','TESTADMIN_','TESTDM_','C:\\Users\\','C:/Users/'
$files=Get-ChildItem $stage -Recurse -File; $textFiles=$files|Where-Object Extension -in '.js','.css','.html','.php','.env','.htaccess','.json','.txt','.svg'; $hits=@(); foreach($file in $textFiles){$text=Get-Content $file.FullName -Raw -ErrorAction SilentlyContinue;foreach($needle in $forbidden){if($text -match [regex]::Escape($needle)){$hits+="$($file.FullName): $needle"}}}
if($hits){$hits|Write-Error;throw 'Deployment package audit failed.'}
$sql=Join-Path $release 'dnd-portal-v4-database.sql'; Get-ChildItem (Join-Path $script:Backend 'database\migrations\*.sql')|Sort-Object Name|Get-Content|Set-Content $sql
if((Get-Content $sql -Raw)-match 'DROP DATABASE|CREATE USER|GRANT '){throw 'SQL audit failed.'}
$zip=Join-Path $release 'dnd-portal-v4-htdocs.zip'; Compress-Archive -Path (Join-Path $stage '*') -DestinationPath $zip -Force
@"
# D&D Portal V4 deployment

1. Back up the existing InfinityFree database and `htdocs`.
2. Confirm that the intended V4 production database is selected; do not use a preview or development database.
3. Import `dnd-portal-v4-database.sql` into the empty production database.
4. Extract `dnd-portal-v4-htdocs.zip` directly into `htdocs`.
5. Confirm Apache rewrite support is active and that `/server/.env`, `/server/src/` and `/server/public/index.php` are not downloadable.
6. If no admin exists, perform the one-time admin bootstrap over HTTPS with the private bootstrap token; never place credentials in this document.
7. Perform a normal admin login.
8. Confirm bootstrap is then rejected and cannot create another admin.
9. Verify the healthcheck.
10. Create and consume an invitation registration test.
11. Create and open a global poll with multiple options.
12. Verify player registration, login and multi-select poll persistence.
13. Verify admin aggregate and per-user poll results.

Rollback: take the site offline, restore the backed-up `htdocs`, restore the database backup, and verify the healthcheck before reopening traffic.
"@ | Set-Content (Join-Path $release 'DEPLOYMENT.md')
$sha256 = { param([string]$Path) $stream=[IO.File]::OpenRead($Path); try { $hash=[Security.Cryptography.SHA256]::Create().ComputeHash($stream); ([BitConverter]::ToString($hash)).Replace('-','') } finally { $stream.Dispose() } }
$zipInfo=Get-Item $zip;$sqlInfo=Get-Item $sql;$zipHash=& $sha256 $zip;$sqlHash=& $sha256 $sql
Write-Portal "Production package created: $zip ($($files.Count) staged files, $($zipInfo.Length) bytes, SHA256 $zipHash)"
Write-Portal "Database SQL: $sql ($($sqlInfo.Length) bytes, SHA256 $sqlHash)"
