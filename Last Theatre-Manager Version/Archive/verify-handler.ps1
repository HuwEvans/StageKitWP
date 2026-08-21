Add-Type -AssemblyName 'System.IO.Compression.FileSystem'

if (Test-Path 'test-extract') { Remove-Item 'test-extract' -Recurse -Force }
[System.IO.Compression.ZipFile]::ExtractToDirectory('Theatre-Manager-3.5.zip', 'test-extract')

# Check if clear button handler is in the extracted admin.js
$adminJs = Get-Content 'test-extract\theatre-manager\assets\js\admin.js' -Raw
if ($adminJs -match 'tm-media-clear-button') {
    Write-Host '✓ Clear button handler found in extracted admin.js'
} else {
    Write-Host '✗ Clear button handler NOT found in ZIP'
}

# Show line count to confirm it grew
$lines = (Get-Content 'test-extract\theatre-manager\assets\js\admin.js' | Measure-Object -Line).Lines
Write-Host "admin.js lines: $lines"
