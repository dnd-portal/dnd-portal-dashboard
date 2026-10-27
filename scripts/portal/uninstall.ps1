$launcher=Join-Path $env:LOCALAPPDATA 'DndPortal\bin\portal.cmd'; if(Test-Path $launcher){Remove-Item -LiteralPath $launcher}; Write-Host 'Removed the V4 portal launcher.'
