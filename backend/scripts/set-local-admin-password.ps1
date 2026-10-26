$ErrorActionPreference = 'Stop'
$repo = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$env:APP_ENV = 'local'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '3307'
$env:DB_NAME = 'dnd_portal_development'
$env:DB_USER = 'dnd_test'
$env:DB_PASSWORD = 'local-test-only'
$secure = Read-Host 'New local admin password (hidden)' -AsSecureString
$ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
try {
    $env:LOCAL_ADMIN_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr)
    Push-Location $repo
    & php backend/scripts/set-local-admin-password.php devadmin
    if ($LASTEXITCODE -ne 0) { throw "Password update failed with exit code $LASTEXITCODE" }
} finally {
    if ($ptr) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr) }
    $env:LOCAL_ADMIN_PASSWORD = ''
    Pop-Location -ErrorAction SilentlyContinue
}
