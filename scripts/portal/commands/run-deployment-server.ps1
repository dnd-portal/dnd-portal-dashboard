. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
$ErrorActionPreference='Stop'
docker compose -f $script:ComposeFile up -d database-preview
for($i=0;$i -lt 30;$i++){ $health=(docker inspect --format '{{.State.Health.Status}}' dnd-portal-v4-mariadb-preview 2>$null); if($health -eq 'healthy'){break}; Start-Sleep -Seconds 2 }
if($health -ne 'healthy'){throw 'V4 preview database did not become healthy.'}
if(!(Test-Path (Join-Path $script:Backend '.env.preview'))){Copy-Item (Join-Path $script:Backend '.env.preview.example') (Join-Path $script:Backend '.env.preview')}
$preview=Get-Content (Join-Path $script:Backend '.env.preview') | Where-Object {$_ -match '=' -and $_ -notmatch '^#'}; foreach($line in $preview){$key,$value=$line.Split('=',2);[Environment]::SetEnvironmentVariable($key,$value)}
Push-Location $script:Backend; php scripts/migrate.php; if($LASTEXITCODE -ne 0){Pop-Location;throw 'Preview migrations failed.'}; php scripts/seed-preview.php; if($LASTEXITCODE -ne 0){Pop-Location;throw 'Preview seed failed.'}; Pop-Location
Write-Portal 'Preview database: dnd_portal_v4_preview / 127.0.0.1:3311'
Write-Portal 'Build first with portal build-deployment after configuring DEPLOY_*; then serve the extracted package with Apache/PHP.'
