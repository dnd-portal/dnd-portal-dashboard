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

1. Back up htdocs and the database.
2. Create the production database and confirm MariaDB 11.4.x.
3. Import `dnd-portal-v4-database.sql` through phpMyAdmin.
4. Extract the htdocs zip directly into htdocs.
5. Over HTTPS, call the one-time `/api/v1/bootstrap/first-admin` action with the private BOOTSTRAP_TOKEN and first-admin details.
6. Log in normally, create an invitation, and verify `/api/v1/health`.
7. Bootstrap is permanently disabled after the first admin and cannot create another admin.
8. Keep this zip private: it contains generated server-side database credentials and the bootstrap token. Roll back by restoring the backups if any check fails.

The server directory is denied by Apache rules; verify `/server/.env`, `/server/src/`, and `/server/public/index.php` are not downloadable.
"@ | Set-Content (Join-Path $release 'DEPLOYMENT.md')
$zipInfo=Get-Item $zip;$sqlInfo=Get-Item $sql;$zipHash=(Get-FileHash $zip -Algorithm SHA256).Hash;$sqlHash=(Get-FileHash $sql -Algorithm SHA256).Hash
Write-Portal "Production package created: $zip ($($files.Count) staged files, $($zipInfo.Length) bytes, SHA256 $zipHash)"
Write-Portal "Database SQL: $sql ($($sqlInfo.Length) bytes, SHA256 $sqlHash)"
