Add-Type -AssemblyName 'System.IO.Compression.FileSystem'

$zip = [System.IO.Compression.ZipFile]::OpenRead('Theatre-Manager-3.5.zip')

# Check plugin file for correct constant
$entry = $zip.GetEntry('theatre-manager/theatre-manager-plugin.php')
if ($entry) {
    $stream = $entry.Open()
    $reader = [System.IO.StreamReader]::new($stream)
    $content = $reader.ReadToEnd()
    if ($content -match "define\('TM_PLUGIN_DIR'") {
        Write-Host '✓ Plugin file: TM_PLUGIN_DIR constant is correct'
    } else {
        Write-Host '✗ Plugin file: TM_PLUGIN_DIR constant is WRONG'
    }
    $reader.Dispose()
    $stream.Dispose()
}

# Check sample-content for awards closing bracket
$entry = $zip.GetEntry('theatre-manager/includes/sample-content.php')
if ($entry) {
    $stream = $entry.Open()
    $reader = [System.IO.StreamReader]::new($stream)
    $content = $reader.ReadToEnd()
    if ($content -match 'TM_Awards.*\);') {
        Write-Host '✓ Sample content: Awards array properly closed'
    } else {
        Write-Host '✗ Sample content: Awards array closing bracket may be missing'
    }
    $reader.Dispose()
    $stream.Dispose()
}

$zip.Dispose()
Write-Host '✓ Package verification complete!'
