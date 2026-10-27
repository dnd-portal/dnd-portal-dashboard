. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
docker compose -f $script:ComposeFile ps
Write-Portal 'Development URL: http://127.0.0.1:5174; phpMyAdmin: http://127.0.0.1:8096'
