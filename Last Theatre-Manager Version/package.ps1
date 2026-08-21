param(
    [string]$Version,
    [string]$SourceDir = 'Theatre-Manager',
    [string]$OutputDir = '.',
    [string]$PackageFolderName = 'theatre-manager'
)

$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

function Add-DirectoryToZip {
    param(
        [System.IO.Compression.ZipArchive]$Archive,
        [string]$BasePath,
        [string]$CurrentPath
    )

    $childItems = Get-ChildItem -Path $CurrentPath -Force
    foreach ($item in $childItems) {
        $relativePath = $item.FullName.Substring($BasePath.Length).TrimStart([System.IO.Path]::DirectorySeparatorChar, [System.IO.Path]::AltDirectorySeparatorChar)
        $entryPath = $relativePath -replace '\\', '/'

        if ($item.PSIsContainer) {
            if (-not [string]::IsNullOrWhiteSpace($entryPath)) {
                $null = $Archive.CreateEntry($entryPath.TrimEnd('/') + '/')
            }
            Add-DirectoryToZip -Archive $Archive -BasePath $BasePath -CurrentPath $item.FullName
            continue
        }

        $entry = $Archive.CreateEntry($entryPath, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        try {
            $fileStream = [System.IO.File]::OpenRead($item.FullName)
            try {
                $fileStream.CopyTo($entryStream)
            }
            finally {
                $fileStream.Dispose()
            }
        }
        finally {
            $entryStream.Dispose()
        }
    }
}

$scriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$sourcePath = Join-Path $scriptRoot $SourceDir

if (-not (Test-Path -Path $sourcePath -PathType Container)) {
    throw "Source directory not found: $sourcePath"
}

$pluginMainFile = Join-Path $sourcePath 'theatre-manager-plugin.php'
if (-not (Test-Path -Path $pluginMainFile -PathType Leaf)) {
    throw "Main plugin file not found: $pluginMainFile"
}

if ([string]::IsNullOrWhiteSpace($Version)) {
    $pluginHeader = Get-Content -Path $pluginMainFile -Raw
    $match = [regex]::Match($pluginHeader, '(?m)^\s*\*\s*Version:\s*([0-9]+(?:\.[0-9]+)*)\s*$')
    if (-not $match.Success) {
        throw 'Could not determine plugin version from theatre-manager-plugin.php header. Pass -Version explicitly.'
    }
    $Version = $match.Groups[1].Value
}

$resolvedOutputDir = Join-Path $scriptRoot $OutputDir
if (-not (Test-Path -Path $resolvedOutputDir -PathType Container)) {
    New-Item -ItemType Directory -Path $resolvedOutputDir | Out-Null
}

# Extract major.minor version for zip filename (e.g., 3.5 from 3.5.0)
$versionParts = $Version -split '\.'
$majorMinorVersion = if ($versionParts.Length -ge 2) { "$($versionParts[0]).$($versionParts[1])" } else { $Version }

$zipName = "Theatre-Manager-$majorMinorVersion.zip"
$zipPath = Join-Path $resolvedOutputDir $zipName

if (Test-Path -Path $zipPath -PathType Leaf) {
    Remove-Item -Path $zipPath -Force
}

# Build in a temp workspace to ensure the zip contains the expected top-level plugin folder.
$tempRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("tm-package-" + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $tempRoot | Out-Null

try {
    $tempPluginPath = Join-Path $tempRoot $PackageFolderName
    Copy-Item -Path $sourcePath -Destination $tempPluginPath -Recurse -Force

    $zipFileStream = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::Create)
    try {
        $zipArchive = New-Object System.IO.Compression.ZipArchive($zipFileStream, [System.IO.Compression.ZipArchiveMode]::Create, $false)
        try {
            $null = $zipArchive.CreateEntry($PackageFolderName.TrimEnd('/') + '/')
            Add-DirectoryToZip -Archive $zipArchive -BasePath $tempRoot -CurrentPath $tempPluginPath
        }
        finally {
            $zipArchive.Dispose()
        }
    }
    finally {
        $zipFileStream.Dispose()
    }
}
finally {
    if (Test-Path -Path $tempRoot) {
        Remove-Item -Path $tempRoot -Recurse -Force
    }
}

Write-Host "Created package: $zipPath"
