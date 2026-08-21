param(
    [switch]$NewFeature
)

Add-Type -AssemblyName 'System.IO.Compression.FileSystem'

# ============ VERSION MANAGEMENT ============
$pluginFile = 'Theatre-Manager\theatre-manager-plugin.php'
$versionPattern = 'Version: ([\d.]+)'

# Read current version
$pluginContent = Get-Content $pluginFile -Raw
if ($pluginContent -match $versionPattern) {
    $currentVersion = $matches[1]
    Write-Host "Current version: $currentVersion"
    
    # Parse version parts
    $versionParts = $currentVersion -split '\.'
    
    if ($NewFeature) {
        # New feature: increment patch, reset build to 1
        $major = [int]$versionParts[0]
        $minor = [int]$versionParts[1]
        $patch = [int]$versionParts[2]
        $patch++
        $newVersion = "$major.$minor.$patch.1"
        Write-Host "New feature detected - incrementing patch version"
    } else {
        # Regular build: increment build number
        if ($versionParts.Count -eq 4) {
            $buildNumber = [int]$versionParts[3] + 1
            $baseVersion = $versionParts[0..2] -join '.'
        } else {
            $buildNumber = 1
            $baseVersion = $currentVersion
        }
        $newVersion = "$baseVersion.$buildNumber"
    }
    
    Write-Host "New version: $newVersion"
    
    # Update plugin header
    $newContent = $pluginContent -replace $versionPattern, "Version: $newVersion"
    Set-Content $pluginFile -Value $newContent
    Write-Host "Updated plugin version to $newVersion"
}

# Remove old package
if (Test-Path 'Theatre-Manager-3.5.zip') { Remove-Item 'Theatre-Manager-3.5.zip' -Force }

# Create ZIP with correct structure
$sourcePath = [System.IO.Path]::GetFullPath('Theatre-Manager')
$zipPath = [System.IO.Path]::GetFullPath('Theatre-Manager-3.5.zip')

# Create new ZIP using ZipFile static method
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')

# Add files with correct path structure
Get-ChildItem -Path $sourcePath -Recurse | ForEach-Object {
    if (-not $_.PSIsContainer) {
        $relativePath = $_.FullName.Substring($sourcePath.Length + 1)
        # Add "theatre-manager/" prefix and convert backslashes to forward slashes
        $entryPath = 'theatre-manager/' + $relativePath.Replace('\', '/')
        
        # Create entry and write file content
        $entry = $zip.CreateEntry($entryPath)
        $stream = $entry.Open()
        $fileStream = [System.IO.File]::OpenRead($_.FullName)
        $fileStream.CopyTo($stream)
        $stream.Dispose()
        $fileStream.Dispose()
    }
}

$zip.Dispose()

Write-Host 'Package created successfully!'
Write-Host "Build version: $newVersion"
Get-Item $zipPath | Select-Object Name, @{N='Size(KB)';E={[math]::Round($_.Length/1KB, 2)}}

