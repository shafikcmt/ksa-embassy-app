    /* ERP Invoice — standard per-passenger format (mPDF; preview = inline,
       download = attachment). Layout: header (logo | agency | barcode)
       → title → 3 meta boxes (Bill To | Invoice Details | Status) → 9-column
       items grid + GRAND TOTAL → subtotal/tax/discount/TOTAL (+ PAID/CANCELLED
       stamp) → total / amount paid / BALANCE DUE → in words → notes → signatures → pinned footer.

       Rules: fixed font scale — 8pt body text, 7.5pt labels AND the whole items
       table (headers + cells), 9pt box headers. Column widths were sized from
       mPDF's own string metrics so every header fits on ONE line at 7.5pt (at
       8pt the 9 headers need ~196mm > the 190mm usable width); amounts use the
       condensed DejaVu cut so 9,999,999.99 fits without wrapping;
       the items table carries autosize="1" so mPDF never shrinks it (shrinking
       would silently change font sizes). mPDF-safe CSS only — tables for all
       layout (no inline-block / display:table / flex). A4 + 10mm margins come
       from PdfGeneratorService::makeMpdf(); no @page rule. Amounts use the ISO
       code (e.g. "BDT"), not the ৳ glyph, so the default font always renders. */
    body  { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 8pt; line-height: 1.3; margin: 0; padding: 0; }
    table { border-collapse: collapse; width: 100%; }

    /* Header */
    .head td { vertical-align: middle; border-bottom: 0.8px solid #cbd5e1; padding: 0 4pt 5pt; }
    .a-name { font-size: 11pt; font-weight: bold; color: #0f172a; }
    .a-rl   { font-size: 9pt; font-weight: bold; color: #334155; margin-top: 2pt; }
    .a-addr { font-size: 8pt; font-style: italic; color: #475569; margin-top: 2pt; }
    .a-contact { font-size: 7.5pt; color: #475569; margin-top: 1.5pt; }
    .bc-num { font-family: dejavusansmono, monospace; font-size: 7pt; color: #334155; letter-spacing: 0.6pt; margin-top: 1pt; }

    /* Title */
    .title td { text-align: center; padding: 6pt 0 5pt; }
    .t-main { color: #0f172a; font-weight: bold; font-size: 14pt; letter-spacing: 1pt; text-decoration: underline; }

    /* Meta boxes — one shared table so all three boxes get the same height */
    .meta td.mh { background-color: #e8e8e8; border: 0.5px solid #333333; font-size: 9pt; font-weight: bold; padding: 4pt 6pt; }
    .meta td.mb { border: 0.5px solid #333333; border-top: none; padding: 4pt 6pt 6pt; vertical-align: top; }
    .meta td.gap { border: none; }
    .kv td { padding: 1.5pt 0; vertical-align: top; }
    .lbl { color: #475569; font-size: 7.5pt; white-space: nowrap; }
    .val { color: #0f172a; font-size: 8pt; font-weight: bold; text-align: right; white-space: nowrap; }
    .bill-name { color: #0f172a; font-size: 9pt; font-weight: bold; }
    .bill-line { color: #475569; font-size: 7.5pt; margin-top: 1.5pt; }

    .badge { font-size: 7.5pt; font-weight: bold; letter-spacing: 0.4pt; padding: 1.5pt 6pt; }
    .b-draft     { background-color: #e5e7eb; color: #1e293b; }
    .b-pending   { background-color: #fcd34d; color: #1e293b; }
    .b-paid      { background-color: #86efac; color: #14532d; }
    .b-cancelled { background-color: #fca5a5; color: #7f1d1d; }
    .b-overdue   { background-color: #e11d48; color: #ffffff; }

    /* Items grid — single-line headers, fixed widths */
    .grid th { background-color: #e8e8e8; color: #1e293b; font-size: 7.5pt; font-weight: bold; text-align: center; white-space: nowrap; padding: 5pt 2.5pt; border: 0.5px solid #333333; vertical-align: middle; }
    .grid td { font-size: 7.5pt; padding: 4pt 2.5pt; border: 0.5px solid #333333; vertical-align: top; }
    .grid tr.alt td { background-color: #f9f9f9; }
    .c { text-align: center; }
    .l { text-align: left; }
    .amt { font-family: dejavusanscondensed, sans-serif; font-weight: bold; text-align: right; white-space: nowrap; }
    .nw  { white-space: nowrap; }
    .due-open { color: #92400e; }
    .grid tr.grand td { background-color: #e8e8e8; font-weight: bold; padding: 5pt 3pt; }

    /* Totals */
    .totals td { font-size: 8pt; padding: 3pt 8pt; }
    .totals .tk { color: #475569; text-align: right; }
    .totals .tv { font-family: dejavusansmono, monospace; font-weight: bold; text-align: right; white-space: nowrap; }
    .totals tr.total td { border-top: 0.8px solid #94a3b8; color: #0f172a; font-weight: bold; font-size: 8.5pt; }
    .totals tr.paid td { color: #047857; }
    .totals tr.grand td { background-color: #1e293b; color: #ffffff; font-size: 9pt; font-weight: bold; padding: 5pt 8pt; border: 0.5px solid #1e293b; }

    .stamp { font-weight: bold; font-size: 12pt; letter-spacing: 3pt; padding: 4pt 8pt; border: 1.5px solid; text-align: center; }
    .stamp-paid      { color: #047857; border-color: #047857; }
    .stamp-cancelled { color: #be123c; border-color: #be123c; }

    /* In words / notes */
    .words td { background-color: #eff6ff; border: 0.5px solid #dbeafe; padding: 4pt 8pt; font-size: 8pt; }
    .w-label { color: #334155; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt; }
    .w-value { color: #0f172a; font-weight: bold; font-style: italic; }
    .notes-h { color: #475569; font-size: 7.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt; margin-bottom: 2pt; }
    .notes   { color: #334155; font-size: 8pt; }

    /* Signatures */
    .sign { margin-top: 48pt; }
    .sign .line { border-top: 0.5px solid #64748b; padding-top: 4pt; font-size: 9pt; font-weight: bold; color: #334155; }

    .foot td { border-top: 0.5px solid #e2e8f0; padding-top: 3pt; color: #999999; font-size: 7pt; }
