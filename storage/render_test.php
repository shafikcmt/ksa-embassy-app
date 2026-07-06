<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\HrProfile;
use App\Support\PrintDataMapper;
use App\Services\BarcodeService;
use Mpdf\Mpdf;

$hr = HrProfile::with(['agent','passport','visa','clearance','otherInfo','agency'])->first();
if (!$hr) { fwrite(STDERR, "No HrProfile found\n"); exit(1); }
$data = PrintDataMapper::forHr($hr);
$barcode = app(BarcodeService::class);
$data = array_merge($data, [
  'topBarcodeText'    => $data['visa_no'] ?: ($data['file_number'] ?? ''),
  'topBarcodeSrc'     => $barcode->make($data['visa_no'] ?: ($data['file_number'] ?? '')),
  'bottomBarcodeText' => $data['passport_no'] ?: '',
  'bottomBarcodeSrc'  => $barcode->make($data['passport_no'] ?: ''),
  '_pdf' => true,
]);
$html = view('prints.hr.application', $data)->render();
if (!is_dir(storage_path('app/mpdf-tmp'))) mkdir(storage_path('app/mpdf-tmp'),0755,true);
// Mirrors PdfGeneratorService::makeMpdf() so this QA render matches production.
$mpdf = new Mpdf(['mode'=>'utf-8','format'=>'A4','margin_top'=>10,'margin_right'=>10,'margin_bottom'=>10,'margin_left'=>10,'tempDir'=>storage_path('app/mpdf-tmp'),'autoScriptToLang'=>true,'autoLangToFont'=>true,'default_font'=>'dejavusans']);
$mpdf->WriteHTML($html);
file_put_contents(storage_path('app/app-test.pdf'), $mpdf->Output('', 'S'));
echo "OK -> storage/app/app-test.pdf\n";
