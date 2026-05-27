<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\EmbassyList;
use App\Models\GeneratedDocument;
use App\Models\HrProfile;
use App\Services\PdfGeneratorService;
use App\Support\PrintDataMapper;

class DocumentController extends Controller
{
    public function __construct(private PdfGeneratorService $pdf) {}

    public function hrDocuments(HrProfile $hr)
    {
        $hr->load(['agent', 'passport', 'visa', 'clearance', 'otherInfo', 'agency']);
        return view('super-admin.hr.documents', compact('hr'));
    }

    public function downloadEmbassyList(EmbassyList $embassyList)
    {
        $embassyList->load(['agency', 'createdBy']);
        $data = PrintDataMapper::forEmbassyList($embassyList);
        GeneratedDocument::log('embassy_list', 'download', null, $embassyList);

        $filename = 'embassy-list-' . $embassyList->list_no;
        return $this->pdf->generateFromView('prints.embassy-list', $data, $filename);
    }
}
