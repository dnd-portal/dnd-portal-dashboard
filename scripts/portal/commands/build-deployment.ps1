. (Join-Path $PSScriptRoot '..\lib\environment.ps1')
$release=Join-Path $script:V4Root 'release'; New-Item -ItemType Directory -Force $release | Out-Null
Push-Location $script:Player; pnpm build; Pop-Location
if (Test-Path (Join-Path $script:Player 'build')) { Compress-Archive -Path (Join-Path $script:Player 'build\*') -DestinationPath (Join-Path $release 'dnd-portal-v4-htdocs.zip') -Force }
Get-ChildItem (Join-Path $script:Backend 'database\migrations\*.sql') | Get-Content | Set-Content (Join-Path $release 'dnd-portal-v4-database.sql')
@'
# D&D Portal V4 deployment

Back up htdocs and the database, import the SQL through phpMyAdmin, then extract the htdocs zip directly into htdocs. Configure the backend environment locally before packaging production credentials. Never commit or share the generated zip.
'@ | Set-Content (Join-Path $release 'DEPLOYMENT.md')
Write-Portal "Release written to $release"
