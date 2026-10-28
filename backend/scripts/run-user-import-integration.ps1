$ErrorActionPreference = 'Stop'
$runId = [DateTime]::UtcNow.ToString('yyyyMMddHHmmss')
$db = "dnd_portal_test_run_$runId"
Write-Host "TEST_DATABASE=$db"
$adminPassword = "Admin-$([Guid]::NewGuid().ToString('N'))-Aa1!"
$importPassword = "Import-$([Guid]::NewGuid().ToString('N'))-Aa1!"
$adminUsername = "admin$runId"
$adminEmail = "admin-$runId@example.test"
$importUsername = "imported$runId"
$importEmail = "imported-$runId@example.test"
$root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$backend = Join-Path $root 'backend'
$player = Join-Path $root 'player'
$phpCommand = (Get-Command php).Source
$phpExe = Join-Path (Split-Path $phpCommand) 'php84\php.exe'
if (-not (Test-Path $phpExe)) { throw 'Herd PHP executable was not found.' }
if (Get-NetTCPConnection -LocalPort 8099 -State Listen -ErrorAction SilentlyContinue) { throw 'Refusing to reuse an occupied test HTTP port.' }
$env:APP_ENV = 'testing'
$env:DB_HOST = '127.0.0.1'; $env:DB_PORT = '3307'; $env:DB_NAME = $db
$env:DB_USER = 'dnd_test'; $env:DB_PASSWORD = 'local-test-only'; $env:TEST_DB_ROOT_PASSWORD = 'local-root-only'
$env:TEST_ADMIN_PASSWORD = $adminPassword; $env:INITIAL_ADMIN_PASSWORD = $adminPassword
$env:TEST_ADMIN_USERNAME = $adminUsername; $env:TEST_ADMIN_EMAIL = $adminEmail
$env:TEST_IMPORT_PASSWORD = $importPassword
$env:TEST_IMPORT_HASH = php -r "echo password_hash(getenv('TEST_IMPORT_PASSWORD'), PASSWORD_DEFAULT);"
$env:TEST_IMPORT_USERNAME = $importUsername; $env:TEST_IMPORT_EMAIL = $importEmail
$env:TEST_DB_NAME = $db; $env:APP_ALLOWED_ORIGIN = 'http://127.0.0.1:5174'; $env:AUTH_COOKIE = 'dnd_session'
$env:TEST_BACKEND_URL = 'http://127.0.0.1:8099'
$env:TEST_PHP_EXE = $phpExe
$compose = Join-Path $backend 'database\compose.test.yml'
$server = $null
docker compose -f $compose up -d | Out-Null
try {
    Start-Sleep -Seconds 12
    php (Join-Path $backend 'scripts\create-test-database.php') $db | Out-Null
    php (Join-Path $backend 'scripts\migrate.php') | Out-Null
    php (Join-Path $backend 'scripts\create-first-admin.php') $adminEmail $adminUsername 'Import Test Admin' | Out-Null
    $verify = php -r "require 'backend/src/Config.php'; \DndPortal\Config::loadDotEnv('backend/.env'); require 'backend/src/Database/Connection.php'; `$q=\DndPortal\Database\Connection::make()->prepare('SELECT password_hash FROM user_credentials JOIN users ON users.id=user_credentials.user_id WHERE users.username=?'); `$q->execute([getenv('TEST_ADMIN_USERNAME')]); `$h=`$q->fetchColumn(); echo password_verify(getenv('TEST_ADMIN_PASSWORD'), `$h) ? '1' : '0';"
    if ($verify -ne '1') { throw 'Test-admin password hash verification failed.' }
    $serverEnv = @{}
    Get-ChildItem Env: | Where-Object Name -in @('APP_ENV','DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASSWORD','APP_ALLOWED_ORIGIN','AUTH_COOKIE') | ForEach-Object { $serverEnv[$_.Name] = $_.Value }
    $stdout = Join-Path $env:TEMP "dnd-import-$runId.log"; $stderr = Join-Path $env:TEMP "dnd-import-$runId.error.log"
    $start = [Diagnostics.ProcessStartInfo]::new()
    $start.FileName = $phpExe; $start.WorkingDirectory = $root; $start.UseShellExecute = $false
    $start.RedirectStandardOutput = $true; $start.RedirectStandardError = $true
    foreach ($entry in $serverEnv.GetEnumerator()) { $start.Environment[$entry.Key] = $entry.Value }
    $publicPath = Join-Path $backend 'public'; $routerPath = Join-Path $backend 'router.php'
    $start.Arguments = "-S 127.0.0.1:8099 -t `"$publicPath`" `"$routerPath`""
    $server = [Diagnostics.Process]::Start($start)
    $stdoutTask = $server.StandardOutput.ReadToEndAsync(); $stderrTask = $server.StandardError.ReadToEndAsync()
    $deadline = (Get-Date).AddSeconds(15)
    do { try { Invoke-WebRequest 'http://127.0.0.1:8099/api/v1/health' -UseBasicParsing -TimeoutSec 2 | Out-Null; break } catch { Start-Sleep -Milliseconds 250 } } while ((Get-Date) -lt $deadline)
    if (-not (Get-NetTCPConnection -LocalPort 8099 -State Listen -ErrorAction SilentlyContinue)) { throw 'Test PHP server did not listen.' }
    Push-Location $player
    pnpm exec playwright test tests/browser/user-import-real.spec.ts tests/browser/invitation-register-real.spec.ts tests/browser/global-poll-multiselect-real.spec.ts --reporter=line
    if ($LASTEXITCODE -ne 0) { throw "Import HTTP integration test failed with exit code $LASTEXITCODE." }
    Pop-Location
    $stored = php -r "require 'backend/src/Config.php'; \DndPortal\Config::loadDotEnv('backend/.env'); require 'backend/src/Database/Connection.php'; `$db=\DndPortal\Database\Connection::make(); `$q=`$db->prepare('SELECT password_hash FROM user_credentials JOIN users ON users.id=user_credentials.user_id WHERE users.username=?'); `$q->execute([getenv('TEST_IMPORT_USERNAME')]); echo hash_equals(getenv('TEST_IMPORT_HASH'), (string) `$q->fetchColumn()) ? 'HASH_BYTE_MATCH=1' : 'HASH_BYTE_MATCH=0'; `$q=`$db->prepare('SELECT COUNT(*) FROM registration_invites WHERE invited_user_id=(SELECT id FROM users WHERE username=?)'); `$q->execute([getenv('TEST_IMPORT_USERNAME')]); echo ((int) `$q->fetchColumn() === 0) ? ' NO_INVITE=1' : ' NO_INVITE=0'; `$q=`$db->query('SELECT COUNT(*) FROM audit_events WHERE event_type=\'user.imported\''); echo ((int) `$q->fetchColumn() > 0) ? ' AUDIT=1' : ' AUDIT=0';"
    Write-Host $stored
    if ($stored -ne 'HASH_BYTE_MATCH=1 NO_INVITE=1 AUDIT=1') { throw 'Database-side import verification failed.' }
} finally {
    if ($server) { Stop-Process -Id $server.Id -Force -ErrorAction SilentlyContinue }
    if ($server) { $server.WaitForExit(3000) | Out-Null; if ($stdoutTask.Result.Contains($env:TEST_IMPORT_HASH) -or $stderrTask.Result.Contains($env:TEST_IMPORT_HASH)) { throw 'Test log contained the password hash.' } }
    $env:TEST_DB_ROOT_PASSWORD = 'local-root-only'; $env:DB_HOST = '127.0.0.1'; $env:DB_PORT = '3307'
    php -r "`$pdo=new PDO('mysql:host=127.0.0.1;port=3307','root',getenv('TEST_DB_ROOT_PASSWORD')); `$pdo->exec('DROP DATABASE IF EXISTS ' . getenv('TEST_DB_NAME'));" 2>$null
    docker compose -f $compose down | Out-Null
}
