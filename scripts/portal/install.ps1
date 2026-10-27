$root=(Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path; $bin=Join-Path $env:LOCALAPPDATA 'DndPortal\bin'; New-Item -ItemType Directory -Force $bin | Out-Null
$launcher=Join-Path $bin 'portal.cmd'; if(Test-Path $launcher){$old=Get-Content $launcher -Raw;if($old -notmatch [regex]::Escape($root)){throw 'Refusing to overwrite an unknown portal command.'}}
"@echo off`r`npowershell -NoProfile -ExecutionPolicy Bypass -File `"$root\scripts\portal\portal.ps1`" %*" | Set-Content $launcher
Write-Host "Installed portal at $launcher. Add $bin to your user PATH, then open a new PowerShell."
