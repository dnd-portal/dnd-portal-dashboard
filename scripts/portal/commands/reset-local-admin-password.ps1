. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
$ErrorActionPreference = 'Stop'

$securePassword = Read-Host 'New password' -AsSecureString
$secureConfirmation = Read-Host 'Repeat new password' -AsSecureString
$passwordPtr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
$confirmationPtr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureConfirmation)
try {
    $password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPtr)
    $confirmation = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($confirmationPtr)
    if ([string]::IsNullOrEmpty($password) -or $password -ne $confirmation) {
        throw 'Refused: passwords must be non-empty and identical.'
    }

    $php = (Get-Command php -ErrorAction Stop).Source
    $script = Join-Path $script:Backend 'scripts\reset-local-admin-password.php'
    $psi = [Diagnostics.ProcessStartInfo]::new()
    $psi.FileName = $php
    $psi.Arguments = '"' + $script + '"'
    $psi.WorkingDirectory = $script:Backend
    $psi.UseShellExecute = $false
    $psi.RedirectStandardInput = $true
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $process = [Diagnostics.Process]::Start($psi)
    $exitCode = 1
    try {
        $process.StandardInput.WriteLine($password)
        $process.StandardInput.Close()
        $output = $process.StandardOutput.ReadToEnd()
        $errorOutput = $process.StandardError.ReadToEnd()
        $process.WaitForExit()
        $exitCode = $process.ExitCode
    } finally {
        if (!$process.HasExited) { $process.Kill() }
        $process.Dispose()
    }
    if ($exitCode -ne 0) {
        if ($errorOutput) { Write-Error $errorOutput.Trim() }
        throw 'Local admin password was not updated.'
    }
    Write-Host 'Local admin password updated.'
} finally {
    if ($passwordPtr -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPtr) }
    if ($confirmationPtr -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($confirmationPtr) }
    $password = $null
    $confirmation = $null
}
