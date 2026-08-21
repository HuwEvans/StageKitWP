Add-Type -AssemblyName 'System.IO.Compression.FileSystem'

$zip = [System.IO.Compression.ZipFile]::OpenRead('Theatre-Manager-3.5.zip')

Write-Host 'First 20 entries in ZIP:'
$zip.Entries | Select-Object FullName -First 20 | Format-Table -AutoSize -Wrap

$zip.Dispose()
