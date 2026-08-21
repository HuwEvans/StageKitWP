Add-Type -AssemblyName 'System.IO.Compression.FileSystem'

# Remove old package if it exists
$zipPath = Join-Path (Get-Location) 'Theatre-Manager-3.5.6-final.zip'
if (Test-Path $zipPath) { Remove-Item $zipPath }

$sourceDir = Join-Path (Get-Location) 'theatre-manager'

# Create zip with flat structure
$zipFile = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')

# Add all files with theatre-manager prefix
Get-ChildItem -Path $sourceDir -Recurse | ForEach-Object {
    if (-not $_.PSIsContainer) {
        $relativePath = $_.FullName.Substring($sourceDir.Length + 1)
        $zipEntryPath = 'theatre-manager/' + $relativePath
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipFile, $_.FullName, $zipEntryPath) | Out-Null
    }
}

$zipFile.Dispose()

Write-Host "Created Theatre-Manager-3.5.6-final.zip"
Get-Item $zipPath | Select-Object Name, @{N='Size(KB)';E={[math]::Round($_.Length/1024,2)}}
