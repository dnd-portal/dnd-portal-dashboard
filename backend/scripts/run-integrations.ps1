param([switch]$KeepDatabase)
$ErrorActionPreference = 'Stop'
$Backend = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$Repo = (Resolve-Path (Join-Path $Backend '..')).Path
$Player = Join-Path $Repo 'player'
$RunId = "$PID$([DateTime]::UtcNow.ToString('yyyyMMddHHmmss'))"
$DbName = "dnd_portal_test_run_$RunId"
$env:APP_ENV = 'testing'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '3307'
$env:DB_NAME = $DbName
$env:DB_USER = 'dnd_test'
$env:DB_PASSWORD = 'local-test-only'
$env:TEST_DB_ROOT_PASSWORD = 'local-root-only'
$env:APP_ALLOWED_ORIGIN = 'http://127.0.0.1:5173'
$env:AUTH_COOKIE = 'dnd_session'
$env:TEST_BACKEND_URL = 'http://127.0.0.1:8099'
$env:TEST_HARNESS_EXTERNAL = '1'
$logRoot = Join-Path $env:TEMP "dnd-portal-integrations-$RunId"
New-Item -ItemType Directory -Force -Path $logRoot | Out-Null
Write-Host "Repository: $Repo"
Write-Host "Test database: $DbName"
if ((Test-NetConnection 127.0.0.1 -Port 3307 -InformationLevel Quiet) -ne $true) { throw 'Test database port 3307 is unavailable.' }
function Invoke-Native([string]$File, [string[]]$Arguments) {
    & $File @Arguments
    if ($LASTEXITCODE -ne 0) { throw "Native command failed ($LASTEXITCODE): $File $($Arguments -join ' ')" }
}
function Wait-Http([string]$Url, [int]$TimeoutSeconds = 30) {
    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    do {
        try { $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 3; if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 500) { return } } catch { }
        Start-Sleep -Milliseconds 250
    } while ((Get-Date) -lt $deadline)
    throw "Timed out waiting for $Url"
}
function Stop-TestPortProcess([int]$Port) {
    $connections = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
    foreach ($connection in $connections) {
        $process = Get-Process -Id $connection.OwningProcess -ErrorAction SilentlyContinue
        throw "Port $Port is already occupied by process $($process.Id) ($($process.ProcessName)); refusing to stop a process not started by this runner."
    }
    for ($attempt = 0; $attempt -lt 20; $attempt++) {
        if (-not (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue)) { break }
        Start-Sleep -Milliseconds 250
    }
    if (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue) { throw "Port $Port remained occupied after stopping the PHP test process." }
}
Stop-TestPortProcess 8099
Invoke-Native 'php' @((Join-Path $Backend 'scripts/create-test-database.php'), $DbName)
Invoke-Native 'php' @((Join-Path $Backend 'scripts/migrate.php'))
Invoke-Native 'php' @((Join-Path $Backend 'scripts/migrate.php'))
$secure = Read-Host 'Integration admin password' -AsSecureString
$ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
try { $env:INITIAL_ADMIN_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr) }
$env:TEST_ADMIN_PASSWORD = $env:INITIAL_ADMIN_PASSWORD
$env:TEST_ADMIN_EMAIL = "admin-$RunId@example.test"
$env:TEST_ADMIN_USERNAME = "admin$RunId"
Invoke-Native 'php' @((Join-Path $Backend 'scripts/create-first-admin.php'), $env:TEST_ADMIN_EMAIL, $env:TEST_ADMIN_USERNAME, 'Integration Admin')
Remove-Item Env:INITIAL_ADMIN_PASSWORD
$backendLog = Join-Path $env:TEMP "dnd-backend-$RunId.log"
$backendErrorLog = Join-Path $env:TEMP "dnd-backend-$RunId.error.log"
$frontendLog = Join-Path $logRoot 'frontend.log'
$frontendErrorLog = Join-Path $logRoot 'frontend.error.log'
$serverEnvironment = @{
    APP_ENV = $env:APP_ENV; DB_HOST = $env:DB_HOST; DB_PORT = $env:DB_PORT; DB_NAME = $env:DB_NAME; DB_USER = $env:DB_USER; DB_PASSWORD = $env:DB_PASSWORD; APP_ALLOWED_ORIGIN = $env:APP_ALLOWED_ORIGIN
}
Write-Host "Backend config: DB_NAME=$($serverEnvironment.DB_NAME) APP_ENV=$($serverEnvironment.APP_ENV)"
$backendProcess = Start-Process php -ArgumentList '-S','127.0.0.1:8099','-t',(Join-Path $Backend 'public'),(Join-Path $Backend 'router.php') -WorkingDirectory $Repo -RedirectStandardOutput $backendLog -RedirectStandardError $backendErrorLog -PassThru
$frontendProcess = $null
try {
    Wait-Http 'http://127.0.0.1:8099/api/v1/health'
    $frontendProcess = Start-Process pnpm -ArgumentList 'dev','--host','127.0.0.1','--port','5173' -WorkingDirectory $Player -RedirectStandardOutput $frontendLog -RedirectStandardError $frontendErrorLog -PassThru
    Wait-Http 'http://127.0.0.1:5173/'
    $env:REAL_BACKEND_EMAIL = $env:TEST_ADMIN_EMAIL
    $env:REAL_BACKEND_PASSWORD = $env:TEST_ADMIN_PASSWORD
    $env:POLL_PLAYER_USERNAME = 'pollplayer'
    $env:POLL_PLAYER_PASSWORD = 'PollPlayer2026!'
    $env:POLL_ADMIN_USERNAME = 'polladmin'
    $env:POLL_ADMIN_PASSWORD = 'PollAdmin2026!'
    $env:PAYMENT_PLAYER_USERNAME = 'paybrowser'
    $env:PAYMENT_PLAYER_PASSWORD = 'PayBrowser2026!'
    Invoke-Native 'php' @((Join-Path $Backend 'tests/integration/auth_flow.php'))
    Invoke-Native 'php' @((Join-Path $Backend 'tests/integration/http_auth_flow.php'))
    Invoke-Native 'php' @((Join-Path $Backend 'tests/integration/campaign_restore.php'))
    Invoke-Native 'php' @((Join-Path $Backend 'tests/integration/poll_validations.php'))
    Invoke-Native 'php' @((Join-Path $Backend 'tests/integration/poll_access_validations.php'))
    Invoke-Native 'php' @((Join-Path $Backend 'tests/integration/payment_validations.php'))
    Invoke-Native 'php' @((Join-Path $Backend 'tests/integration/prepare_poll_browser_fixture.php'))
    Invoke-Native 'php' @((Join-Path $Backend 'tests/integration/prepare_payment_browser_fixture.php'))
    Invoke-Native 'php' @((Join-Path $Backend 'tests/integration/auth_matrix.php'))
    Invoke-Native 'php' @((Join-Path $Backend 'tests/integration/admin_concurrency.php'))
    # The matrix owns and cleans up its own admins; it does not touch the
    # runner-admin or depend on the restoring fixture above.
    Invoke-Native 'php' @((Join-Path $Backend 'tests/integration/admin_concurrency_matrix.php'))
    # Browser tests run after auth/security tests; re-seed only the isolated poll
    # fixture so their real login requests are not polluted by prior test rate limits.
    Invoke-Native 'php' @((Join-Path $Backend 'tests/integration/prepare_poll_browser_fixture.php'))
    $env:CI = '1'
    Push-Location $Player
    Invoke-Native 'pnpm' @('exec','playwright','test','tests/browser/auth-real.spec.ts','tests/browser/access-control.spec.ts','tests/browser/poll-real.spec.ts','tests/browser/payment-real.spec.ts')
    Pop-Location
} finally {
    if ($frontendProcess -and -not $frontendProcess.HasExited) { Stop-Process -Id $frontendProcess.Id -Force }
    if ($backendProcess -and -not $backendProcess.HasExited) { Stop-Process -Id $backendProcess.Id -Force }
    Write-Host "Logs retained in $logRoot"
    Remove-Item Env:TEST_DB_ROOT_PASSWORD,Env:REAL_BACKEND_EMAIL,Env:REAL_BACKEND_PASSWORD -ErrorAction SilentlyContinue
    if (-not $KeepDatabase) { Write-Host "Database $DbName retained for safety; remove it only with an explicit operator action." }
}
