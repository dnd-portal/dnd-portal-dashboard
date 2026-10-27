. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
. (Join-Path $PSScriptRoot '..\lib\processes.ps1')
docker compose -f $script:ComposeFile up -d database phpmyadmin
Push-Location $script:Backend; if (!(Test-Path .env)) { Copy-Item .env.example .env }; php scripts/migrate.php; php scripts/seed-v4.php; Pop-Location
Write-Portal 'MariaDB 11.4 is at 127.0.0.1:3310; phpMyAdmin is http://127.0.0.1:8096'
Start-PortalProcess 'backend-development' 'php' @('-S','127.0.0.1:8110','-t','public','router.php') $script:Backend
Start-PortalProcess 'frontend-development' 'pnpm' @('dev','--host','127.0.0.1','--port','5174') $script:Player
for($i=0;$i-lt 30;$i++){try{if((Invoke-WebRequest http://127.0.0.1:8110/api/v1/health -UseBasicParsing).StatusCode -eq 200 -and (Invoke-WebRequest http://127.0.0.1:5174/ -UseBasicParsing).StatusCode -eq 200){break}}catch{};Start-Sleep 1}
Write-Portal 'Portal: http://127.0.0.1:5174/';Write-Portal 'Login: http://127.0.0.1:5174/login';Write-Portal 'Register: http://127.0.0.1:5174/register';Write-Portal 'Dashboard: http://127.0.0.1:5174/dashboard';Write-Portal 'Admin: http://127.0.0.1:5174/admin';Write-Portal 'Backend API: http://127.0.0.1:8110/api/v1/health'
Set-Content (Join-Path $script:V4Root 'runtime\active-mode') 'development'
