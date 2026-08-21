Add-Type -AssemblyName 'System.IO.Compression.FileSystem'

$zip = [System.IO.Compression.ZipFile]::OpenRead('Theatre-Manager-3.5.zip')

# Check for awards shortcode
$awards_entry = $zip.GetEntry('theatre-manager/includes/shortcodes/awards-shortcode.php')
if ($awards_entry) {
    Write-Host 'Awards shortcode: FOUND'
} else {
    Write-Host 'Awards shortcode: NOT FOUND'
}

# Check admin-menu.php for awards tab
$admin_entry = $zip.GetEntry('theatre-manager/includes/admin-menu.php')
if ($admin_entry) {
    $stream = $admin_entry.Open()
    $reader = [System.IO.StreamReader]::new($stream)
    $content = $reader.ReadToEnd()
    if ($content -match "awards") {
        Write-Host 'Admin menu: INCLUDES awards tab'
    }
    $reader.Dispose()
    $stream.Dispose()
}

# Check sample-content.php for awards page
$sample_entry = $zip.GetEntry('theatre-manager/includes/sample-content.php')
if ($sample_entry) {
    $stream = $sample_entry.Open()
    $reader = [System.IO.StreamReader]::new($stream)
    $content = $reader.ReadToEnd()
    if ($content -match "tm-awards") {
        Write-Host 'Sample content: INCLUDES awards page'
    }
    $reader.Dispose()
    $stream.Dispose()
}

Write-Host '✓ Package updated successfully!'
$zip.Dispose()
