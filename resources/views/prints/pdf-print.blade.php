{{--
    Direct-print wrapper for mPDF documents (Invoice, Payment Voucher).
    The "Print" button opens this page: it embeds the SAME inline PDF in an
    iframe and opens the browser print dialog once it has loaded — so the
    printed output is byte-identical to the downloaded PDF (pinned footers,
    watermarks and other mPDF-only features stay intact).
    Expects: $title, $pdfUrl, optional $downloadUrl / $backUrl.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
<style>
    html, body { margin: 0; height: 100%; background: #525659; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
    .toolbar { display: flex; align-items: center; gap: 8px; height: 44px; padding: 0 14px; box-sizing: border-box;
               background: #1a1f2e; color: #fff; font-size: 13px; }
    .toolbar .spacer { flex: 1; }
    .toolbar a, .toolbar button { color: #fff; background: #374151; border: 0; border-radius: 6px; padding: 6px 12px;
                                  font: inherit; text-decoration: none; cursor: pointer; }
    .toolbar .primary { background: #2563eb; }
    iframe { display: block; width: 100%; height: calc(100% - 44px); border: 0; }
</style>
</head>
<body>
    <div class="toolbar">
        <strong>{{ $title }}</strong>
        <span class="spacer"></span>
        @isset($backUrl)<a href="{{ $backUrl }}">&larr; Back</a>@endisset
        @isset($downloadUrl)<a href="{{ $downloadUrl }}">Download PDF</a>@endisset
        <button type="button" class="primary" id="printBtn">Print</button>
    </div>
    <iframe id="pdfFrame" src="{{ $pdfUrl }}" title="{{ $title }}"></iframe>

    <script>
        (function () {
            var frame = document.getElementById('pdfFrame');
            function printPdf() {
                try {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                } catch (e) {
                    // Browser refused to print the embedded viewer → open the PDF itself.
                    window.location.href = frame.src;
                }
            }
            document.getElementById('printBtn').addEventListener('click', printPdf);
            // Auto-open the print dialog once the PDF viewer has loaded.
            frame.addEventListener('load', function () { setTimeout(printPdf, 500); });
        })();
    </script>
</body>
</html>
