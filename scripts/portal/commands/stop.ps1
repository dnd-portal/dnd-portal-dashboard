. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
docker compose -f $script:ComposeFile stop
