<?php
$pdfPath = 'imagenes/turismo_andalucia.pdf';
$pdfExists = file_exists($pdfPath);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>turismo andalucia</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.min.mjs" type="module"></script>
    <style>
        :root {
            --bg: #f4f6f8;
            --text: #14202f;
            --panel: #ffffff;
            --line: #d7dfe8;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: linear-gradient(180deg, #eef2f6 0%, #f9fbfd 100%);
            color: var(--text);
            font-family: "Segoe UI", Tahoma, sans-serif;
        }

        .page-wrap {
            max-width: 1080px;
            margin: 0 auto;
            padding: 20px 16px 40px;
            box-sizing: border-box;
        }

        .header {
            position: sticky;
            top: 0;
            z-index: 2;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(5px);
            border-bottom: 1px solid var(--line);
            padding: 12px 16px;
            margin: -20px -16px 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            text-transform: lowercase;
            letter-spacing: 0.02em;
        }

        .status {
            margin: 8px 0 0;
            font-size: 14px;
            color: #42596f;
        }

        .viewer {
            display: grid;
            gap: 18px;
        }

        .pdf-page {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 8px 24px rgba(26, 39, 55, 0.08);
        }

        .pdf-page canvas {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 8px;
        }

        .page-label {
            margin: 0 0 8px;
            font-size: 13px;
            color: #496176;
        }

        .missing-file {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8d1a1a;
            text-align: center;
            padding: 24px;
            box-sizing: border-box;
            font-size: 15px;
        }
    </style>
</head>
<body>
<?php if ($pdfExists): ?>
    <main class="page-wrap">
        <header class="header">
            <h1>turismo andalucia</h1>
            <p id="status" class="status">Cargando documento...</p>
        </header>

        <section id="viewer" class="viewer" aria-live="polite"></section>
    </main>

    <script type="module">
        import * as pdfjsLib from 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.min.mjs';

        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.worker.min.mjs';

        const statusEl = document.getElementById('status');
        const viewerEl = document.getElementById('viewer');
        const pdfUrl = '<?php echo htmlspecialchars($pdfPath, ENT_QUOTES, 'UTF-8'); ?>';

        async function renderPdf() {
            try {
                const loadingTask = pdfjsLib.getDocument(pdfUrl);
                const pdf = await loadingTask.promise;

                statusEl.textContent = `Documento cargado. Total de paginas: ${pdf.numPages}`;

                for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
                    const page = await pdf.getPage(pageNumber);
                    const viewport = page.getViewport({ scale: 1.5 });

                    const pageContainer = document.createElement('article');
                    pageContainer.className = 'pdf-page';

                    const label = document.createElement('p');
                    label.className = 'page-label';
                    label.textContent = `Pagina ${pageNumber}`;

                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d', { alpha: false });

                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    pageContainer.appendChild(label);
                    pageContainer.appendChild(canvas);
                    viewerEl.appendChild(pageContainer);

                    await page.render({
                        canvasContext: context,
                        viewport
                    }).promise;
                }
            } catch (error) {
                statusEl.textContent = 'No se pudo cargar el PDF.';
                console.error(error);
            }
        }

        renderPdf();
    </script>
<?php else: ?>
    <div class="missing-file">
        No se encontro el PDF en: <?php echo htmlspecialchars($pdfPath, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>
</body>
</html>
