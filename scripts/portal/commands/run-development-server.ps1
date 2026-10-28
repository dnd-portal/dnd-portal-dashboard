. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
. (Join-Path $PSScriptRoot '..\lib\processes.ps1')
Assert-PortalFrontendPort
$completed=$false
try {
    Set-Content (Join-Path $script:V4Root 'runtime\active-mode') 'development'
    $backendEnv=Join-Path $script:Backend '.env';if(!(Test-Path $backendEnv)){Copy-Item (Join-Path $script:Backend '.env.example') $backendEnv}
    $envValues=@{};foreach($line in Get-Content $backendEnv){if($line -match '^\s*([^#=]+)=(.*)$'){$envValues[$matches[1].Trim()]=$matches[2].Trim()}}
    $pmaEnv=Join-Path $script:V4Root 'runtime\phpmyadmin.env';New-Item -ItemType Directory -Force (Split-Path $pmaEnv)|Out-Null;@("PMA_USER=$($envValues.DB_USER)","PMA_PASSWORD=$($envValues.DB_PASSWORD)")|Set-Content $pmaEnv
    docker compose -f $script:ComposeFile up -d database phpmyadmin
    Push-Location $script:Backend;try{$localEnv=Get-Content .env|Where-Object {$_ -match '=' -and $_ -notmatch '^#'};foreach($line in $localEnv){$key,$value=$line.Split('=',2);[Environment]::SetEnvironmentVariable($key.Trim(),$value.Trim())};$env:APP_ENV='local';$env:DB_HOST='127.0.0.1';$env:DB_PORT='3310';$env:DB_NAME='dnd_portal_v4_development';$env:DB_USER='dnd_v4';$env:DB_PASSWORD='local-v4-only';$env:APP_ALLOWED_ORIGIN='http://127.0.0.1:5174,http://localhost:5174';php scripts/migrate.php}finally{Pop-Location}
    Write-Portal 'MariaDB 11.4 is at 127.0.0.1:3310; phpMyAdmin is http://127.0.0.1:8096 (login with the local dnd_v4 user)'
    $phpCommand=(Get-Command php -ErrorAction Stop).Source;$phpBinary=if($phpCommand -match '\.bat$'){Join-Path (Split-Path $phpCommand) 'php84\php.exe'}else{$phpCommand};if(!(Test-Path $phpBinary)){throw 'PHP executable not found.'}
    $nodes=@(Get-Command node.exe -All -ErrorAction SilentlyContinue|ForEach-Object Source);$nodeBinary=$nodes|Where-Object {$_ -notmatch '\\Volta\\'}|Select-Object -First 1;if(!$nodeBinary){$nodeBinary=$nodes|Select-Object -First 1};if(!$nodeBinary){throw 'Node executable not found.'}
    $viteEntry=Join-Path $script:Player 'node_modules\vite\bin\vite.js';$supervisor=Join-Path $script:V4Root 'scripts\portal\development-supervisor.mjs';if(!(Test-Path $viteEntry)){throw 'Vite entrypoint not found.'}
    & $nodeBinary $supervisor $script:V4Root $script:Backend $phpBinary $viteEntry (Join-Path $script:V4Root 'runtime') (Join-Path $script:V4Root 'logs');if($LASTEXITCODE -ne 0){throw "Development supervisor exited with code $LASTEXITCODE."};$completed=$true
}catch{Write-Error "V4 development startup failed: $($_.Exception.Message)";throw}finally{if(!$completed){Stop-PortalProcesses};docker compose -f $script:ComposeFile stop database phpmyadmin|Out-Null;Remove-Item (Join-Path $script:V4Root 'runtime\active-mode') -Force -ErrorAction SilentlyContinue;Write-Portal 'Development processes and V4 containers stopped.'}
