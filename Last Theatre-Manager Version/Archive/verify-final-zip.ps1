Add-Type -AssemblyName 'System.IO.Compression.FileSystem'
$zip = [System.IO.Compression.ZipFile]::OpenRead('Theatre-Manager-3.5.zip')
Write-Host "ZIP file entries (first 20):"
$entries = @($zip.Entries | Select-Object -First 20)
$entries | Select-Object FullName
$zip.Dispose()
