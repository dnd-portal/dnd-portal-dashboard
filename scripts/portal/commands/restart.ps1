. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
& (Join-Path $PSScriptRoot 'stop.ps1'); & (Join-Path $PSScriptRoot 'run-development-server.ps1')
