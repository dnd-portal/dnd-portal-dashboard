$root=(Resolve-Path (Join-Path $PSScriptRoot '..\..\..')).Path
$entry=Join-Path $root 'player\node_modules\vite\bin\vite.js'
$node=@(Get-Command node.exe -All | ForEach-Object Source | Where-Object {$_ -notmatch '\\Volta\\'})[0]
if(!$node){throw 'A non-Volta node.exe was not found.'}
$psi=[Diagnostics.ProcessStartInfo]::new();$psi.FileName=$node;$psi.WorkingDirectory=Join-Path $root 'player';$psi.UseShellExecute=$false;$psi.CreateNoWindow=$true;$psi.RedirectStandardInput=$true;$psi.RedirectStandardOutput=$true;$psi.RedirectStandardError=$true;$psi.Environment['CI']='true';$psi.Arguments='"'+$entry+'" dev --host 127.0.0.1 --port 5174 --strictPort'
$p=[Diagnostics.Process]::new();$p.StartInfo=$psi;if(!$p.Start()){throw 'Could not start Vite regression process.'}
$stdin=$p.StandardInput;$stdout=$p.StandardOutput;$stderr=$p.StandardError
try {Start-Sleep -Seconds 10;if($p.HasExited){throw "Vite exited early with code $($p.ExitCode)."};$response=Invoke-WebRequest http://127.0.0.1:5174/ -UseBasicParsing -TimeoutSec 3;if($response.StatusCode -ne 200){throw "Vite returned HTTP $($response.StatusCode)."};Write-Output 'PASS: Vite remains alive with CI=true and an open stdin pipe.'}
finally {$stdin.Dispose();if(!$p.HasExited){$p.Kill()};$p.Dispose()}
