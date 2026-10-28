. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
$logRoot=Join-Path $script:V4Root 'logs'
$found=$false
foreach($name in 'backend-development','frontend-development'){
    $path=Join-Path $logRoot "$name.log"
    $errorPath=Join-Path $logRoot "$name.error.log"
    if(Test-Path $path){$found=$true;Write-Host "--- $name ---";Get-Content $path}
    if(Test-Path $errorPath){$found=$true;Write-Host "--- $name errors ---";Get-Content $errorPath}
}
if(!$found){Write-Portal 'No V4 development logs exist yet.'}
