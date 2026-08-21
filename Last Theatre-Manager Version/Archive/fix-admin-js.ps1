$file = 'c:\Tools\Repos\theatre-manager\theatre-manager\theatre-manager\assets\js\admin.js'

# Read all content
$content = Get-Content $file -Raw

# Check if already added
if ($content -match 'tm-media-clear-button') {
    Write-Host 'Handler already present'
    exit
}

# Build the clear button handler
$handler = @'

    $(document).on('click', '.tm-media-clear-button', function (e) {
        e.preventDefault();
        const button = $(this);
        const targetId = button.data('target');
        const previewId = button.data('preview');
        const idTargetId = button.data('id-target');

        // Clear the URL input field
        $('#' + targetId).val('');

        // Clear the ID field if present
        if (idTargetId) {
            $('#' + idTargetId).val('');
        }

        // Hide the preview image
        if (previewId) {
            $('#' + previewId).hide();
        }
    });
'@

# Insert before the closing });
$newContent = $content -replace '(\}\);)$', ($handler + "`n`n`$1")

# Write back
$newContent | Set-Content $file -Encoding UTF8

Write-Host 'Clear button handler added'
