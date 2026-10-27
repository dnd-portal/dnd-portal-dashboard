. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
docker compose -f $script:ComposeFile up -d database phpmyadmin
Push-Location $script:Backend; if (!(Test-Path .env)) { Copy-Item .env.example .env }; php scripts/migrate.php; php scripts/seed-v4.php; Pop-Location
Write-Portal 'MariaDB 11.4 is at 127.0.0.1:3310; phpMyAdmin is http://127.0.0.1:8096'
Write-Portal 'Start backend separately: php -S 127.0.0.1:8110 router.php -t public'
Write-Portal 'Start frontend separately: Set-Location player; pnpm dev --host 127.0.0.1 --port 5174'
