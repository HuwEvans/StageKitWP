Add-Type -AssemblyName 'System.IO.Compression.FileSystem'

$zip = [System.IO.Compression.ZipFile]::OpenRead('Theatre-Manager-3.5.zip')

# Check for awards page in sample-content
$entry = $zip.GetEntry('theatre-manager/includes/sample-content.php')
if ($entry) {
    $stream = $entry.Open()
    $reader = [System.IO.StreamReader]::new($stream)
    $content = $reader.ReadToEnd()
    if ($content -match "'TM_Awards'") {
        Write-Host 'Awards page: FOUND in sample-content.php'
    } else {
        Write-Host 'Awards page: NOT FOUND in sample-content.php'
    }
    $reader.Dispose()
    $stream.Dispose()
}

$zip.Dispose()
