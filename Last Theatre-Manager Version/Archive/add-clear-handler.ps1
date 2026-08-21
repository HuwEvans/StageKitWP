Add-Type -AssemblyName System.Text.RegularExpressions

$filePath = 'c:\Tools\Repos\theatre-manager\theatre-manager\theatre-manager\assets\js\admin.js'
$content = Get-Content $filePath -Raw

# Check if clear button handler already exists
if ($content -like '*tm-media-clear-button*') {
    Write-Host 'Clear button handler already exists'
} else {
    # New handler code
    $newHandler = @'

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

    # Replace closing }); at the end with handler + closing
    $content = $content -replace '\}\);$', ($newHandler + "`n});")
    
    Set-Content $filePath $content -Encoding UTF8
    Write-Host 'Clear button handler added successfully'
    
    # Verify
    if ((Get-Content $filePath -Raw) -like '*tm-media-clear-button*') {
        Write-Host 'Verification: Handler confirmed in file'
    }
}
