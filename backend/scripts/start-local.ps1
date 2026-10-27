param(
    [switch]$SeedDemo,
    [switch]$Stop,
    [switch]$RestartProject
)

$ErrorActionPreference = 'Stop'
$repo = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$backend = Join-Path $repo 'backend'
$player = Join-Path $repo 'player'
$envFile = Join-Path $backend '.env.local'
$frontendOrigin = 'http://127.0.0.1:5173'
$backendOrigin = 'http://127.0.0.1:8099'
$registryPath = Join-Path $env:LOCALAPPDATA 'DndPortal\local-processes.json'

function Write-State([string]$State, [string]$Message) { Write-Host "[$State] $Message" }
function Invoke-Native([string]$File, [string[]]$CommandArgs) {
    Write-Host "> $File $($CommandArgs -join ' ')"
    & $File @CommandArgs
    if ($LASTEXITCODE -ne 0) { throw "$File failed with exit code $LASTEXITCODE" }
}
function Get-PortOwners([int]$Port) {
    @(Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
        Select-Object -ExpandProperty OwningProcess -Unique)
}
function Get-ProcessInfo([int]$ProcessId) {
    $process = Get-CimInstance Win32_Process -Filter "ProcessId = $ProcessId" -ErrorAction SilentlyContinue
    if (-not $process) { return $null }
    [pscustomobject]@{
        Pid = $ProcessId; Name = $process.Name; ExecutablePath = $process.ExecutablePath
        CommandLine = $process.CommandLine; CreationDate = [string]$process.CreationDate; ParentPid = [int]$process.ParentProcessId
    }
}
function Test-ProjectProcess([object]$Process, [int]$Port) {
    if (-not $Process) { return $false }
    $command = [string]$Process.CommandLine
    $path = [string]$Process.ExecutablePath
    return $command -match [regex]::Escape($repo) -and
        $command -match [regex]::Escape("127.0.0.1:$Port") -and
        (($path -and $path.StartsWith($repo, [StringComparison]::OrdinalIgnoreCase)) -or
            $command -match '(?i)(php|node|pnpm)')
}
function Read-Registry {
    if (-not (Test-Path $registryPath)) { return @() }
    try { return @((Get-Content -Raw $registryPath | ConvertFrom-Json)) } catch { return @() }
}
function Write-Registry([object[]]$Entries) {
    New-Item -ItemType Directory -Force -Path (Split-Path $registryPath) | Out-Null
    $Entries | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $registryPath
}
function Remove-StaleRegistryEntries {
    $live = @()
    foreach ($entry in (Read-Registry)) {
        $process = Get-ProcessInfo ([int]$entry.Pid)
        if ($process -and $process.CommandLine -eq $entry.CommandLine -and $process.ExecutablePath -eq $entry.ExecutablePath -and $process.CreationDate -eq $entry.CreationDate) { $live += $entry }
    }
    Write-Registry $live
    return $live
}
function Wait-Port([int]$Port, [int]$Seconds = 45) {
    for ($i = 0; $i -lt $Seconds; $i++) {
        if (Get-PortOwners $Port) { return }
        Start-Sleep 1
    }
    throw "Timed out waiting for 127.0.0.1:$Port"
}
function Test-Http([string]$Url) {
    try {
        $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 3
        return $response.StatusCode -ge 200 -and $response.StatusCode -lt 500
    } catch { return $false }
}
function Load-LocalEnv([string]$Path) {
    if (-not (Test-Path $Path)) { throw "Missing local environment file: $Path" }
    foreach ($line in Get-Content -LiteralPath $Path) {
        if ($line -match '^\s*#' -or $line -match '^\s*$') { continue }
        if ($line -notmatch '^\s*([A-Za-z_][A-Za-z0-9_]*)=(.*)$') { throw "Invalid local env line in $Path" }
        $value = $Matches[2].Trim()
        if ($value.Length -ge 2 -and (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'")))) { $value = $value.Substring(1, $value.Length - 2) }
        [Environment]::SetEnvironmentVariable($Matches[1], $value, 'Process')
    }
}
function Show-Conflict([int]$Port, [object]$Process) {
    $name = if ($Process) { $Process.Name } else { 'unknown' }
    $blockedPid = if ($Process) { $Process.Pid } else { 'unknown' }
    Write-State 'BLOCKED' "Port $Port is occupied by PID $blockedPid ($name)."
    throw "Inspect safely with: Get-CimInstance Win32_Process -Filter 'ProcessId = $blockedPid'"
}
function Get-DescendantPids([int]$ParentPid) {
    $children = @(Get-CimInstance Win32_Process -Filter "ParentProcessId = $ParentPid" -ErrorAction SilentlyContinue)
    foreach ($child in $children) {
        [int]$child.ProcessId
        Get-DescendantPids ([int]$child.ProcessId)
    }
}
function Stop-ProjectTree([int]$Pid) {
    foreach ($childPid in (Get-DescendantPids $Pid | Sort-Object -Descending)) {
        Stop-Process -Id $childPid -Force -ErrorAction SilentlyContinue
    }
    Stop-Process -Id $Pid -Force -ErrorAction SilentlyContinue
}
function Stop-RegisteredProject {
    $entries = Remove-StaleRegistryEntries
    foreach ($entry in $entries) {
        $process = Get-ProcessInfo ([int]$entry.Pid)
        if (-not $process -or $process.CommandLine -ne $entry.CommandLine -or $process.ExecutablePath -ne $entry.ExecutablePath -or $process.CreationDate -ne $entry.CreationDate) { continue }
        Write-State 'STOPPED' "PID $($entry.Pid) ($($entry.Service))"
        Stop-ProjectTree ([int]$entry.Pid)
    }
    Write-Registry @()
}
function Start-ProjectProcess([string]$Service, [string]$File, [string[]]$Arguments, [string]$WorkingDirectory, [string]$Log, [string]$ErrorLog) {
    $process = Start-Process $File -ArgumentList $Arguments -WorkingDirectory $WorkingDirectory -PassThru -RedirectStandardOutput $Log -RedirectStandardError $ErrorLog
    $info = Get-ProcessInfo $process.Id
    $entries = @(Read-Registry) + [pscustomobject]@{ Service = $Service; Pid = $process.Id; ExecutablePath = $info.ExecutablePath; CommandLine = $info.CommandLine; CreationDate = $info.CreationDate; StartedAt = (Get-Date).ToString('o'); Repository = $repo }
    Write-Registry $entries
    Write-State 'STARTED' "$Service (PID $($process.Id))"
    return $process
}
function Preflight-Port([int]$Port, [string]$HealthUrl) {
    $owners = @(Get-PortOwners $Port)
    if (-not $owners) { return 'FREE' }
    $healthy = Test-Http $HealthUrl
    foreach ($ownerPid in $owners) {
        $info = Get-ProcessInfo $ownerPid
        if ($healthy -and (Test-ProjectProcess $info $Port)) { return 'OWNED_HEALTHY' }
        Show-Conflict $Port $info
    }
}

if ($Stop) { Stop-RegisteredProject; exit 0 }

Load-LocalEnv $envFile
$preflight = @{ Backend = Preflight-Port 8099 "$backendOrigin/api/v1/health"; Frontend = Preflight-Port 5173 "$frontendOrigin/" }
if ($RestartProject) {
    Stop-RegisteredProject
    $preflight = @{ Backend = Preflight-Port 8099 "$backendOrigin/api/v1/health"; Frontend = Preflight-Port 5173 "$frontendOrigin/" }
}

$backendProcess = $null
$frontendProcess = $null
try {
    Push-Location $repo
    $missingServer = $preflight.Backend -eq 'FREE' -or $preflight.Frontend -eq 'FREE'
    if ($missingServer) {
        docker compose -f backend/database/compose.test.yml up -d
        if ($LASTEXITCODE -ne 0) { throw 'Docker Compose failed.' }
        Wait-Port 3307
        $env:APP_ENV = 'local'; $env:HOSTING_CHECK_ENABLED = '0'; $env:APP_ALLOWED_ORIGIN = $frontendOrigin
        $env:AUTH_COOKIE_SAMESITE = 'Lax'; $env:VITE_BACKEND_URL = $backendOrigin; $env:DB_HOST = '127.0.0.1'; $env:DB_PORT = '3307'; $env:DB_NAME = 'dnd_portal_development'; $env:DB_USER = 'dnd_test'; $env:DB_PASSWORD = 'local-test-only'; $env:LOCAL_DB_ROOT_PASSWORD = 'local-root-only'
        Invoke-Native 'php' @('backend/scripts/ensure-development-database.php')
        Invoke-Native 'php' @('backend/scripts/migrate.php')
        if ($SeedDemo -or $env:LOCAL_SEED_DEMO -eq 'true') { Invoke-Native 'php' @('backend/scripts/seed-local-demo.php') }
        Invoke-Native 'php' @('backend/scripts/ensure-local-admin.php')
    }
    if ($preflight.Backend -eq 'FREE') { $backendProcess = Start-ProjectProcess 'backend' 'php' @('-S','127.0.0.1:8099','-t',(Join-Path $backend 'public'),(Join-Path $backend 'router.php')) $repo (Join-Path $repo 'backend-local.log') (Join-Path $repo 'backend-local-error.log') } elseif ($preflight.Backend -eq 'OWNED_HEALTHY') { Write-State 'REUSED' "Backend: $backendOrigin" }
    if ($preflight.Frontend -eq 'FREE') { $frontendProcess = Start-ProjectProcess 'frontend' 'pnpm' @('dev','--host','127.0.0.1','--port','5173') $player (Join-Path $repo 'frontend-local.log') (Join-Path $repo 'frontend-local-error.log') } elseif ($preflight.Frontend -eq 'OWNED_HEALTHY') { Write-State 'REUSED' "Frontend: $frontendOrigin" }
    Wait-Port 8099; Wait-Port 5173
    Write-Host "Frontend URL: $frontendOrigin/"; Write-Host "Backend URL:  $backendOrigin/"; Write-Host "Login identifier: $env:LOCAL_ADMIN_EMAIL or $env:LOCAL_ADMIN_USERNAME"
    Write-Host 'Stop safely: & .\backend\scripts\start-local.ps1 -Stop'; Write-Host 'Restart owned processes: & .\backend\scripts\start-local.ps1 -RestartProject'
    while ($true) { Start-Sleep 2 }
} finally {
    if ($frontendProcess) { Stop-ProjectTree $frontendProcess.Id }
    if ($backendProcess) { Stop-ProjectTree $backendProcess.Id }
    Remove-StaleRegistryEntries | Out-Null
    Pop-Location -ErrorAction SilentlyContinue
}
