Add-Type -AssemblyName 'System.IO.Compression.FileSystem'

# Clean up old packages
@('Theatre-Manager-3.5.6-final.zip', 'Theatre-Manager-3.5.zip') | ForEach-Object {
    if (Test-Path $_) { Remove-Item $_ }
}

$zipPath = Join-Path (Get-Location) 'Theatre-Manager-3.5.zip'
$sourceDirAbsolute = Join-Path (Get-Location) 'theatre-manager'

$zipFile = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')

Get-ChildItem -Path $sourceDirAbsolute -Recurse -File | ForEach-Object {
    # Get relative path using absolute path
    $relativePath = $_.FullName.Substring($sourceDirAbsolute.Length + 1)
    # Convert backslashes to forward slashes for ZIP
    $zipEntryPath = ('theatre-manager/' + $relativePath).Replace('\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipFile, $_.FullName, $zipEntryPath) | Out-Null
}

$zipFile.Dispose()

Write-Host "Created Theatre-Manager-3.5.zip with correct structure"
Get-Item $zipPath | Select-Object Name, @{N='Size(KB)';E={[math]::Round($_.Length/1024,2)}}
