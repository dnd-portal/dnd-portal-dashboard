. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
$ErrorActionPreference='Stop'
& (Join-Path $PSScriptRoot 'build-deployment.ps1') -Preview
if($LASTEXITCODE -ne 0){throw 'Preview deployment package build failed.'}
docker compose -f $script:ComposeFile up -d database-preview deployment-preview
for($i=0;$i -lt 30;$i++){ $health=(docker inspect --format '{{.State.Health.Status}}' dnd-portal-v4-mariadb-preview 2>$null); if($health -eq 'healthy'){break}; Start-Sleep -Seconds 2 }
if($health -ne 'healthy'){throw 'V4 preview database did not become healthy.'}
if(!(Test-Path (Join-Path $script:Backend '.env.preview'))){Copy-Item (Join-Path $script:Backend '.env.preview.example') (Join-Path $script:Backend '.env.preview')}
$preview=Get-Content (Join-Path $script:Backend '.env.preview') | Where-Object {$_ -match '=' -and $_ -notmatch '^#'}; foreach($line in $preview){$key,$value=$line.Split('=',2);[Environment]::SetEnvironmentVariable($key,$value)}
$env:APP_ENV='preview';$env:DB_HOST='127.0.0.1';$env:DB_PORT='3311';$env:DB_NAME='dnd_portal_v4_preview';$env:DB_USER='dnd_v4_preview';$env:DB_PASSWORD='local-v4-preview-only'
Push-Location $script:Backend; php scripts/migrate.php; if($LASTEXITCODE -ne 0){Pop-Location;throw 'Preview migrations failed.'}; php scripts/seed-preview.php; if($LASTEXITCODE -ne 0){Pop-Location;throw 'Preview seed failed.'}; Pop-Location
for($i=0;$i -lt 30;$i++){try{$response=Invoke-WebRequest 'http://127.0.0.1:8184/' -UseBasicParsing -TimeoutSec 2;if($response.StatusCode -eq 200 -and $response.Content.Length -gt 100){break}}catch{};Start-Sleep -Seconds 2}
if(!$response -or $response.StatusCode -ne 200 -or $response.Content.Length -le 100){throw 'Deployment preview did not serve a non-empty homepage.'}
try{$api=Invoke-WebRequest 'http://127.0.0.1:8184/api/v1/health' -UseBasicParsing -TimeoutSec 2;if($api.StatusCode -ne 200){throw 'API health failed.'}}catch{throw 'Deployment preview API readiness failed.'}
Write-Portal 'Mode: deployment preview';Write-Portal 'Apache/frontend/API: http://127.0.0.1:8184';Write-Portal 'Preview database: dnd_portal_v4_preview / 127.0.0.1:3311';Write-Portal 'phpMyAdmin: http://127.0.0.1:8096';Write-Portal "ZIP SHA256: $((Get-FileHash (Join-Path $script:V4Root 'release\dnd-portal-v4-htdocs.zip')).Hash)"
Set-Content (Join-Path $script:V4Root 'runtime\active-mode') 'deployment-preview'
