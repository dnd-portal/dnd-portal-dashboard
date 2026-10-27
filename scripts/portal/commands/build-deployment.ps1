param([switch]$Preview)
. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
$ErrorActionPreference='Stop'
$release=Join-Path $script:V4Root 'release'; $stage=Join-Path $script:V4Root 'runtime\package-stage'
if(Test-Path $release){Remove-Item -LiteralPath $release -Recurse -Force}; New-Item -ItemType Directory -Force $release,$stage | Out-Null
if(!$Preview){$prodKeys='DEPLOY_DB_HOST','DEPLOY_DB_PORT','DEPLOY_DB_NAME','DEPLOY_DB_USER','DEPLOY_DB_PASSWORD';foreach($key in $prodKeys){if([string]::IsNullOrWhiteSpace([Environment]::GetEnvironmentVariable($key))){throw "Missing $key. Refusing to create a production package without explicit deployment configuration."}}}
Push-Location $script:Player; pnpm build | Out-Host; Pop-Location
Copy-Item (Join-Path $script:Player 'build\*') $stage -Recurse -Force
New-Item -ItemType Directory -Force (Join-Path $stage 'server\public'),(Join-Path $stage 'server\src') | Out-Null
Copy-Item (Join-Path $script:Backend 'public\index.php') (Join-Path $stage 'server\public\index.php')
Copy-Item (Join-Path $script:Backend 'src\*') (Join-Path $stage 'server\src') -Recurse -Force
@"
APP_ENV=production
APP_ALLOWED_ORIGIN=$(if($Preview){'preview-injected'}else{$env:DEPLOY_APP_ALLOWED_ORIGIN})
DB_HOST=$(if($Preview){'preview-injected'}else{$env:DEPLOY_DB_HOST})
DB_PORT=$(if($Preview){'3306'}else{$env:DEPLOY_DB_PORT})
DB_NAME=$(if($Preview){'preview-injected'}else{$env:DEPLOY_DB_NAME})
DB_USER=$(if($Preview){'preview-injected'}else{$env:DEPLOY_DB_USER})
DB_PASSWORD=$(if($Preview){'preview-injected'}else{$env:DEPLOY_DB_PASSWORD})
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
$forbidden='localhost','127.0.0.1','dnd_portal_v4_development','dnd_portal_v4_preview','node_modules','V4-TYPE-A-INVITE','V4-TYPE-B-INVITE','v4-player','v4-admin','TESTUSER_','TESTADMIN_','TESTDM_','C:\\Users\\','C:/Users/'
$files=Get-ChildItem $stage -Recurse -File; $textFiles=$files|Where-Object Extension -in '.js','.css','.html','.php','.env','.htaccess','.json','.txt','.svg'; $hits=@(); foreach($file in $textFiles){$text=Get-Content $file.FullName -Raw -ErrorAction SilentlyContinue;foreach($needle in $forbidden){if($text -match [regex]::Escape($needle)){$hits+="$($file.FullName): $needle"}}}
if($hits){$hits|Write-Error;throw 'Deployment package audit failed.'}
$sql=Join-Path $release 'dnd-portal-v4-database.sql'; Get-ChildItem (Join-Path $script:Backend 'database\migrations\*.sql')|Sort-Object Name|Get-Content|Set-Content $sql
if((Get-Content $sql -Raw)-match 'DROP DATABASE|CREATE USER|GRANT '){throw 'SQL audit failed.'}
$zip=Join-Path $release 'dnd-portal-v4-htdocs.zip'; Compress-Archive -Path (Join-Path $stage '*') -DestinationPath $zip -Force
@"
# D&D Portal V4 deployment

1. Back up htdocs and the database.
2. Confirm MariaDB 11.4.x and import the SQL through phpMyAdmin.
3. Extract the htdocs zip directly into htdocs.
4. Test the core URLs and `/api/v1/health`.
5. Keep this zip private: it contains generated server-side database credentials.
6. Roll back by restoring the backups if any check fails.

The server directory is denied by Apache rules; verify `/server/.env`, `/server/src/`, and `/server/public/index.php` are not downloadable.
"@ | Set-Content (Join-Path $release 'DEPLOYMENT.md')
$zipInfo=Get-Item $zip;$sqlInfo=Get-Item $sql;$zipHash=(Get-FileHash $zip -Algorithm SHA256).Hash;$sqlHash=(Get-FileHash $sql -Algorithm SHA256).Hash
Write-Portal "Production package created: $zip ($($files.Count) staged files, $($zipInfo.Length) bytes, SHA256 $zipHash)"
Write-Portal "Database SQL: $sql ($($sqlInfo.Length) bytes, SHA256 $sqlHash)"
