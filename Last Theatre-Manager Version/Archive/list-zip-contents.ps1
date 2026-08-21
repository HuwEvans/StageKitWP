Add-Type -AssemblyName 'System.IO.Compression.FileSystem'

$zip = [System.IO.Compression.ZipFile]::OpenRead('Theatre-Manager-3.5.zip')

Write-Host "Shortcodes in ZIP:"
$zip.Entries | Where-Object {$_.FullName -like '*shortcodes*'} | Select-Object FullName | Format-Table -AutoSize

$zip.Dispose()
