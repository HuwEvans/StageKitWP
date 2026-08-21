Add-Type -AssemblyName 'System.IO.Compression.FileSystem'
$zip = [System.IO.Compression.ZipFile]::OpenRead('Theatre-Manager-3.5.6-final.zip')
Write-Host "First 15 entries in ZIP:"
$zip.Entries | Select-Object -First 15 | Select-Object FullName
$zip.Dispose()
