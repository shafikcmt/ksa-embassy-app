    /* Payment Voucher only: restrained blue/gray palette, shared metadata rows,
       single classification row and light amount summary. All layout uses
       mPDF-safe tables; A4 + 10mm margins come from PdfGeneratorService.
       Existing historical tax/discount amounts and status details stay visible. */
    body  { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 9pt; line-height: 1.4; margin: 0; padding: 0; }
    table { border-collapse: collapse; width: 100%; }

    .head td { vertical-align: middle; border-bottom: 0.5pt solid #d5dee9; padding: 0 4pt 6pt; }
    .a-name { font-size: 12pt; font-weight: bold; color: #0f172a; }
    .a-rl   { font-size: 9pt; font-weight: bold; color: #334155; margin-top: 2pt; }
    .a-addr { font-size: 8pt; color: #64748b; margin-top: 2pt; }
    .bc-num { font-family: dejavusansmono, monospace; font-size: 7pt; color: #334155; letter-spacing: 0.6pt; margin-top: 1pt; }

    .title td { text-align: center; padding: 8pt 0 7pt; }
    .t-main { color: #1e40af; font-weight: bold; font-size: 13.5pt; letter-spacing: 0.8pt; }

    .meta .box-h { background-color: #f1f5f9; border: 0.5pt solid #d5dee9; color: #334155; font-size: 8.5pt; font-weight: bold; padding: 7pt 9pt; }
    .meta .box-body { border: 0.5pt solid #d5dee9; padding: 9pt; vertical-align: top; height: 78pt; }
    .meta .gap { width: 2%; }
    .fields td { padding: 0 0 5pt; vertical-align: top; }
    .lbl { color: #64748b; font-size: 7.5pt; padding-right: 5pt; white-space: nowrap; }
    .val { color: #0f172a; font-size: 8pt; font-weight: bold; text-align: right; }
    .p-name { color: #0f172a; font-size: 9pt; font-weight: bold; }
    .p-line { color: #475569; font-size: 8pt; margin-top: 4pt; }

    .badge { font-size: 7.5pt; font-weight: bold; letter-spacing: 0.4pt; padding: 1.5pt 6pt; }
    .b-draft     { background-color: #e5e7eb; color: #1e293b; }
    .b-approved  { background-color: #dbeafe; color: #1e40af; }
    .b-paid      { background-color: #dcfce7; color: #166534; }
    .b-cancelled { background-color: #ffe4e6; color: #9f1239; }

    .purpose td { border: 0.5pt solid #d5dee9; background-color: #f8fafc; padding: 9pt 11pt; font-size: 9pt; vertical-align: middle; }
    .purpose .ph { background-color: #f1f5f9; color: #475569; font-size: 8pt; font-weight: bold; width: 36%; }
    .purpose .head-value { color: #1e40af; font-size: 10pt; font-weight: bold; }
    .remarks td { padding: 7pt 10pt; border-bottom: 0.5pt solid #e2e8f0; font-size: 8pt; color: #475569; vertical-align: top; }
    .remarks .ph { width: 18%; color: #64748b; }

    .grid th { background-color: #e8e8e8; color: #1e293b; font-size: 8pt; font-weight: bold; text-align: center; white-space: nowrap; padding: 5pt 4pt; border: 0.5px solid #333333; vertical-align: middle; }
    .grid td { font-size: 8pt; padding: 4pt 4pt; border: 0.5px solid #333333; vertical-align: top; }
    .grid tr.alt td { background-color: #f9f9f9; }
    .grid tr.grand td { background-color: #e8e8e8; font-weight: bold; padding: 5pt 4pt; }
    .c { text-align: center; }
    .l { text-align: left; }
    .amt { font-family: dejavusanscondensed, sans-serif; font-weight: bold; text-align: right; white-space: nowrap; }
    .sub { color: #64748b; font-size: 7.5pt; margin-top: 1pt; }

    .totals { border: 0.5pt solid #cbd9ed; background-color: #eff6ff; }
    .totals td { font-size: 8pt; padding: 5pt 10pt; }
    .totals .tk { color: #475569; text-align: right; }
    .totals .tv { font-family: dejavusansmono, monospace; font-weight: bold; text-align: right; white-space: nowrap; }
    .totals tr.grand td { color: #1e40af; font-size: 15pt; font-weight: bold; padding: 4pt 12pt 12pt; text-align: right; }
    .totals .amount-label { color: #475569; font-size: 8pt; font-weight: bold; text-align: right; padding: 10pt 12pt 0; }

    .stamp { font-weight: bold; font-size: 12pt; letter-spacing: 3pt; padding: 4pt 8pt; border: 1.5px solid; text-align: center; }
    .stamp-paid      { color: #047857; border-color: #047857; }
    .stamp-cancelled { color: #be123c; border-color: #be123c; }

    .words td { background-color: #f1f5f9; border: 0.5pt solid #d5dee9; padding: 10pt 12pt; font-size: 9pt; }
    .w-label { color: #64748b; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
    .w-value { color: #0f172a; font-weight: bold; }

    .sign { margin-top: 34pt; }
    .sign .line { border-top: 0.5pt solid #94a3b8; padding-top: 6pt; font-size: 8pt; font-weight: bold; color: #475569; }
    .signature-name { padding-top: 3pt; text-align: center; font-size: 7.5pt; color: #64748b; }

    .foot td { border-top: 0.5px solid #e2e8f0; padding-top: 3pt; color: #999999; font-size: 7pt; }
