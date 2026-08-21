/* Render first page thumbnails for PDFs using PDF.js
 * Elements: <canvas class="tm-pdf-canvas" data-pdf="/path/to/file.pdf" data-width="300"></canvas>
 */
(function () {
    function safeInt(v, def) { var n = parseInt(v, 10); return isNaN(n) ? def : n; }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.pdfjsLib === 'undefined' && typeof window.pdfjsLib === 'undefined') {
            // If pdfjs not loaded via CDN, bail silently
            return;
        }

        // Set workerSrc if available
        try {
            if (window.pdfjsLib && window.pdfjsLib.GlobalWorkerOptions) {
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
            }
        } catch (e) {
            // ignore
        }

        var canvases = document.querySelectorAll('.tm-pdf-canvas');
        canvases.forEach(function (canvas) {
            var url = canvas.dataset.pdf;
            if (!url) return;
            var desiredWidth = safeInt(canvas.dataset.width, 300);

            // load document
            window.pdfjsLib.getDocument(url).promise.then(function (pdf) {
                return pdf.getPage(1).then(function (page) {
                    var viewport = page.getViewport({ scale: 1 });
                    var scale = desiredWidth / viewport.width;
                    var scaledViewport = page.getViewport({ scale: scale });

                    canvas.width = Math.floor(scaledViewport.width);
                    canvas.height = Math.floor(scaledViewport.height);
                    // Make the canvas scale responsively to its container
                    canvas.style.width = '100%';
                    canvas.style.height = 'auto';
                    var ctx = canvas.getContext('2d');
                    var renderContext = {
                        canvasContext: ctx,
                        viewport: scaledViewport
                    };
                    page.render(renderContext);
                });
            }).catch(function (err) {
                // On error, hide canvas and leave a link (the shortcodes include a link fallback)
                canvas.style.display = 'none';
                console.error('PDF preview error for', url, err);
            });
        });
    });
})();
