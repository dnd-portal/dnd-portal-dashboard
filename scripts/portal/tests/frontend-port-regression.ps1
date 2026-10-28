. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
$listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback, 5174)
$listener.Start()
try {
    $output = powershell.exe -NoLogo -NoProfile -Command "portal run-development-server" 2>&1 | Out-String
    if ($LASTEXITCODE -eq 0 -or $output -notmatch 'port 5174|5174') {
        throw 'Regression failed: starter did not refuse the occupied canonical frontend port.'
    }
    if ($output -match '5175') {
        throw 'Regression failed: starter attempted an alternate frontend port.'
    }
    Write-Host 'PASS: occupied 5174 is reported and V4 startup does not fall back to 5175.'
} finally {
    $listener.Stop()
}
