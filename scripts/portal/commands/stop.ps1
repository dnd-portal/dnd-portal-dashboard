. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
. (Join-Path $PSScriptRoot '..\lib\processes.ps1')
$mode=if(Test-Path (Join-Path $script:V4Root 'runtime\active-mode')){(Get-Content (Join-Path $script:V4Root 'runtime\active-mode') -Raw).Trim()}else{''}
if($mode -eq 'deployment-preview'){docker compose -f $script:ComposeFile stop deployment-preview database-preview}
elseif($mode -eq 'development'){Stop-PortalProcesses;docker compose -f $script:ComposeFile stop database phpmyadmin}
else{
    $registered=(@('backend-development','frontend-development') | ForEach-Object {Test-Path (Join-Path $script:Runtime "$_.pid")}) -contains $true
    if($registered){Stop-PortalProcesses;docker compose -f $script:ComposeFile stop database phpmyadmin | Out-Null;Write-Portal 'Stopped registered V4 development processes and containers.'}
    else{Write-Portal 'No active V4 mode recorded; refusing to guess which resources to stop.'}
}
if($mode){Remove-Item (Join-Path $script:V4Root 'runtime\active-mode') -Force -ErrorAction SilentlyContinue}
