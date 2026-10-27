param([Parameter(Position=0)][string]$Command='help')
$file=Join-Path $PSScriptRoot "commands\$Command.ps1"
if (!(Test-Path $file)) { throw "Unknown portal command: $Command" }
& $file
