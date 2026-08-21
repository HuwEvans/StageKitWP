Add-Type -AssemblyName 'System.IO.Compression.FileSystem'

# Extract and check version
if (Test-Path 'version-check') { Remove-Item 'version-check' -Recurse -Force }
[System.IO.Compression.ZipFile]::ExtractToDirectory('Theatre-Manager-3.5.zip', 'version-check')

$pluginFile = 'version-check\theatre-manager\theatre-manager-plugin.php'
$content = Get-Content $pluginFile

# Find version
$versionLine = $content | Where-Object { $_ -match 'Version:' } | Select-Object -First 1
Write-Host "Plugin header: $versionLine"

$constantLine = $content | Where-Object { $_ -match "define('THEATRE_MANAGER_VERSION'" } | Select-Object -First 1
Write-Host "Version constant: $constantLine"
