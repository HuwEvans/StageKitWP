Remove-Item 'Theatre-Manager-3.5.zip' -Force -ErrorAction SilentlyContinue

Add-Type -AssemblyName 'System.IO.Compression.FileSystem'

$source = 'Theatre-Manager'
$zip = 'Theatre-Manager-3.5.zip'

[System.IO.Compression.ZipFile]::CreateFromDirectory($source, $zip)

Write-Host 'Package rebuilt'
Get-Item $zip | Select-Object @{N='File';E={$_.Name}}, @{N='Size(KB)';E={[math]::Round($_.Length/1KB, 2)}}
