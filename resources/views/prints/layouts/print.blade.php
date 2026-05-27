<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 10pt;
    color: #000;
    background: #fff;
    line-height: 1.4;
    margin: 0;
    padding: 0;
}
.ar {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    direction: rtl;
    text-align: right;
}
table { width: 100%; border-collapse: collapse; }
td, th { padding: 3px 5px; vertical-align: top; }
.bordered td, .bordered th { border: 1px solid #333; }
.page { width: 190mm; margin: 0 auto; padding: 5mm 0; }
.text-center { text-align: center; }
.text-right { text-align: right; }
.bold { font-weight: bold; }
.label { color: #555; font-size: 8.5pt; }
.val { font-weight: bold; }
.section-title {
    background: #2c3e50;
    color: #fff;
    padding: 4px 8px;
    font-size: 10pt;
    font-weight: bold;
    margin: 8px 0 4px;
}
.header-bar {
    border-bottom: 3px double #2c3e50;
    margin-bottom: 8px;
    padding-bottom: 6px;
}
.signature-row { margin-top: 40px; }
.sig-cell { width: 33%; text-align: center; }
.sig-line { border-top: 1px solid #000; margin-top: 35px; padding-top: 4px; font-size: 8.5pt; }
.field-row { margin-bottom: 3px; }
.field-label { display: inline-block; width: 140px; font-size: 8.5pt; color: #555; }
.field-val { font-weight: bold; font-size: 9.5pt; }
.box { border: 1px solid #999; padding: 3px 5px; min-height: 18px; }
.photo-box {
    border: 1px solid #999;
    width: 80px; height: 100px;
    text-align: center; font-size: 7.5pt; color: #aaa;
    display: inline-block; vertical-align: top;
    padding-top: 38px;
}
@page { size: A4 portrait; margin: 10mm; }
@media print {
    .no-print { display: none !important; }
    body { font-size: 9.5pt; }
}
</style>
</head>
<body>
@yield('content')
</body>
</html>
