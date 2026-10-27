$script:V4Root = (Resolve-Path (Join-Path $PSScriptRoot '..\..\..')).Path
$script:ComposeFile = Join-Path $script:V4Root 'docker-compose.v4.yml'
$script:Backend = Join-Path $script:V4Root 'backend'
$script:Player = Join-Path $script:V4Root 'player'
function Write-Portal([string]$Message) { Write-Host "[portal] $Message" }
