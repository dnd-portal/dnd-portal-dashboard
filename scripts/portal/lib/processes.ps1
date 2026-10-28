$script:Runtime=Join-Path $script:V4Root 'runtime'
function Start-PortalProcess([string]$Name,[string]$File,[string[]]$Arguments,[string]$WorkingDirectory){$f=Join-Path $script:Runtime "$Name.pid";if(Test-Path $f){$old=[int](Get-Content $f);if(Get-Process -Id $old -ErrorAction SilentlyContinue){return}};$log=Join-Path $script:V4Root "logs\$Name.log";$err=Join-Path $script:V4Root "logs\$Name.error.log";New-Item -ItemType Directory -Force (Split-Path $log)|Out-Null;$p=Start-Process $File -ArgumentList $Arguments -WorkingDirectory $WorkingDirectory -RedirectStandardOutput $log -RedirectStandardError $err -PassThru;Set-Content $f $p.Id}
function Get-PortalPortOwner([int]$Port){Get-NetTCPConnection -LocalAddress '127.0.0.1' -LocalPort $Port -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1}
function Get-PortalProcessCommandLine([int]$Id){(Get-CimInstance Win32_Process -Filter "ProcessId = $Id" -ErrorAction SilentlyContinue).CommandLine}
function Get-PortalProcessName([int]$Id){(Get-Process -Id $Id -ErrorAction SilentlyContinue).ProcessName}
function Stop-PortalProcessTree([int]$Id){$children=Get-CimInstance Win32_Process -Filter "ParentProcessId = $Id" -ErrorAction SilentlyContinue;foreach($child in $children){Stop-PortalProcessTree ([int]$child.ProcessId)};if(Get-Process -Id $Id -ErrorAction SilentlyContinue){Stop-Process -Id $Id -Force -ErrorAction SilentlyContinue}}
function Stop-PortalProcesses{foreach($name in 'backend-development','frontend-development'){$f=Join-Path $script:Runtime "$name.pid";if(Test-Path $f){$id=[int](Get-Content $f);if(Get-Process -Id $id -ErrorAction SilentlyContinue){Stop-PortalProcessTree $id};Remove-Item $f -Force -ErrorAction SilentlyContinue}}}
function Assert-PortalFrontendPort{
    $owner=Get-PortalPortOwner 5174
    if(!$owner){return}
    $ownerPid=[int]$owner.OwningProcess
    $registered=Join-Path $script:Runtime 'frontend-development.pid'
    $registeredId=if(Test-Path $registered){[int](Get-Content $registered)}else{0}
    $commandLine=Get-PortalProcessCommandLine $ownerPid
    if($ownerPid -eq $registeredId -or ($commandLine -and $commandLine.Contains($script:V4Root))){
        Write-Portal "Registered V4 frontend process owns 5174 (PID $ownerPid); stopping it before restart."
        Stop-PortalProcessTree $ownerPid
        Remove-Item $registered -Force -ErrorAction SilentlyContinue
        return
    }
    $name=Get-PortalProcessName $ownerPid
    throw "Frontend port 5174 is occupied by an unknown process. PID=$ownerPid ProcessName=$name CommandLine=$commandLine. Inspect it before stopping it; no V4 services were started."
}
