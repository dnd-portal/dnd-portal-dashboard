$ErrorActionPreference = 'Stop'
$repo = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$backend = Join-Path $repo 'backend'
$player = Join-Path $repo 'player'
$envFile = Join-Path $backend '.env.local'
$frontendOrigin = 'http://127.0.0.1:5173'
$backendOrigin = 'http://127.0.0.1:8099'
$phpProcess = $null
$viteProcess = $null
function Invoke-Native([string]$File, [string[]]$CommandArgs) { Write-Host "> $File $($CommandArgs -join ' ')"; & $File @CommandArgs; if ($LASTEXITCODE -ne 0) { throw "$File failed with exit code $LASTEXITCODE" } }
function Wait-Port([string]$HostName, [int]$Port, [int]$Seconds = 45) {
    for ($i=0; $i -lt $Seconds; $i++) {
        $tcp = [Net.Sockets.TcpClient]::new()
        try { $tcp.Connect($HostName, $Port); if ($tcp.Connected) { $tcp.Dispose(); return } } catch { } finally { $tcp.Dispose() }
        Start-Sleep 1
    }
    throw "Timed out waiting for $HostName`:$Port"
}
function Assert-PortFree([int]$Port) {
    $listener = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
    if ($listener) { throw "Port $Port is already in use by PID $($listener[0].OwningProcess). Stop that process explicitly before starting local development." }
}
function Wait-DatabaseHealthy([int]$Seconds = 45) {
    for ($i=0; $i -lt $Seconds; $i++) {
        $health = docker inspect --format '{{.State.Health.Status}}' dnd-portal-test-database 2>$null
        if ($LASTEXITCODE -eq 0 -and $health.Trim() -eq 'healthy') { return }
        Start-Sleep 1
    }
    throw 'The Docker MariaDB test database did not become healthy.'
}
try {
    Push-Location $repo
    docker compose -f backend/database/compose.test.yml up -d
    if ($LASTEXITCODE -ne 0) { throw 'Docker Compose failed.' }
    Wait-Port '127.0.0.1' 3307
    Wait-DatabaseHealthy
    $env:APP_ENV='local'; $env:HOSTING_CHECK_ENABLED='0'; $env:APP_ALLOWED_ORIGIN=$frontendOrigin
    $env:AUTH_COOKIE_SAMESITE='Lax'; $env:VITE_BACKEND_URL=$backendOrigin; $env:DB_HOST='127.0.0.1'; $env:DB_PORT='3307'; $env:DB_NAME='dnd_portal_development'; $env:DB_USER='dnd_test'; $env:DB_PASSWORD='local-test-only'; $env:LOCAL_DB_ROOT_PASSWORD='local-root-only'
    if (-not (Test-Path $envFile)) {
        @(
            'APP_ENV=local'
            "APP_ALLOWED_ORIGIN=$frontendOrigin"
            'AUTH_COOKIE_SAMESITE=Lax'
            'DB_HOST=127.0.0.1'
            'DB_PORT=3307'
            'DB_NAME=dnd_portal_development'
            'DB_USER=dnd_test'
            'DB_PASSWORD=local-test-only'
            'LOCAL_DB_ROOT_PASSWORD=local-root-only'
        ) | Set-Content -Path $envFile -Encoding utf8
    }
    Invoke-Native 'php' @('backend/scripts/ensure-development-database.php')
    Invoke-Native 'php' @('backend/scripts/migrate.php')
    $email = Read-Host 'Local admin email (existing active portal admin is reused)'
    $username = Read-Host 'Local admin username'
    $displayName = Read-Host 'Local admin display name'
    $env:INITIAL_ADMIN_PASSWORD=''
    & php backend/scripts/ensure-local-admin.php $email $username $displayName
    if ($LASTEXITCODE -eq 3) { $secure = Read-Host 'New local admin password (hidden)' -AsSecureString; $ptr=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure); try { $env:INITIAL_ADMIN_PASSWORD=[Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr); & php backend/scripts/ensure-local-admin.php $email $username $displayName; if ($LASTEXITCODE -ne 0) { throw 'Local admin creation failed.' } } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr); $env:INITIAL_ADMIN_PASSWORD='' } } elseif ($LASTEXITCODE -ne 0) { throw 'Local admin check failed.' }
    Assert-PortFree 8099; Assert-PortFree 5173
    $phpProcess = Start-Process php -ArgumentList '-S','127.0.0.1:8099','-t',(Join-Path $backend 'public'),(Join-Path $backend 'router.php') -WorkingDirectory $repo -PassThru -RedirectStandardOutput (Join-Path $repo 'backend-local.log') -RedirectStandardError (Join-Path $repo 'backend-local-error.log')
    $viteProcess = Start-Process pnpm -ArgumentList 'dev','--host','127.0.0.1','--port','5173' -WorkingDirectory $player -PassThru -RedirectStandardOutput (Join-Path $repo 'frontend-local.log') -RedirectStandardError (Join-Path $repo 'frontend-local-error.log')
    Wait-Port '127.0.0.1' 8099; Wait-Port '127.0.0.1' 5173
    Write-Host "Open $frontendOrigin/ (do not use localhost)."
    Write-Host "Login identifier: $email or $username"
    Write-Host 'Press Ctrl+C to stop only the processes started by this script.'
    while ($true) { Start-Sleep 2 }
} finally {
    if ($viteProcess -and -not $viteProcess.HasExited) { Stop-Process -Id $viteProcess.Id -Force -ErrorAction SilentlyContinue }
    if ($phpProcess -and -not $phpProcess.HasExited) { Stop-Process -Id $phpProcess.Id -Force -ErrorAction SilentlyContinue }
    Pop-Location -ErrorAction SilentlyContinue
}
