<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\EmbassyList;
use App\Models\GeneratedDocument;
use App\Models\HrProfile;
use App\Services\PdfGeneratorService;
use App\Support\PrintDataMapper;

class DocumentController extends Controller
{
    public function __construct(private PdfGeneratorService $pdf) {}

    // ──────────────────────────────────────────────────────────────────────
    // HR DOCUMENTS HUB
    // ──────────────────────────────────────────────────────────────────────

    public function hrDocuments(HrProfile $hr)
    {
        $this->authorizeHr($hr);
        $hr->load(['agent', 'passport', 'visa', 'clearance', 'otherInfo', 'agency']);
        return view('agency.hr.documents', compact('hr'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // PREVIEWS (HTML — no PDF limit counted)
    // ──────────────────────────────────────────────────────────────────────

    public function previewApplication(HrProfile $hr)
    {
        $this->authorizeHr($hr);
        $hr->load(['agent', 'passport', 'visa', 'clearance', 'otherInfo', 'agency']);
        $data = PrintDataMapper::forHr($hr);
        GeneratedDocument::log('application', 'preview', $hr);
        return view('prints.hr.application', $data);
    }

    public function previewForwardingLetter(HrProfile $hr)
    {
        $this->authorizeHr($hr);
        $hr->load(['agent', 'passport', 'visa', 'clearance', 'otherInfo', 'agency']);
        $data = PrintDataMapper::forHr($hr);
        GeneratedDocument::log('forwarding_letter', 'preview', $hr);
        return view('prints.hr.forwarding-letter', $data);
    }

    public function previewEmploymentAgreement(HrProfile $hr)
    {
        $this->authorizeHr($hr);
        $hr->load(['agent', 'passport', 'visa', 'clearance', 'otherInfo', 'agency']);
        $data = PrintDataMapper::forHr($hr);
        GeneratedDocument::log('employment_agreement', 'preview', $hr);
        return view('prints.hr.employment-agreement', $data);
    }

    public function previewChecklist(HrProfile $hr)
    {
        $this->authorizeHr($hr);
        $hr->load(['agent', 'passport', 'visa', 'clearance', 'otherInfo', 'agency']);
        $data = PrintDataMapper::forHr($hr);
        GeneratedDocument::log('checklist', 'preview', $hr);
        return view('prints.hr.checklist', $data);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DOWNLOADS (mPDF — counted against plan limit)
    // ──────────────────────────────────────────────────────────────────────

    public function downloadApplication(HrProfile $hr)
    {
        $this->authorizeHr($hr);
        $this->enforcePdfLimit();
        $hr->load(['agent', 'passport', 'visa', 'clearance', 'otherInfo', 'agency']);
        $data = PrintDataMapper::forHr($hr);
        GeneratedDocument::log('application', 'download', $hr);
        $filename = 'application-' . str_replace(' ', '-', strtolower($hr->full_name_en));
        return $this->pdf->generateFromView('prints.hr.application', $data, $filename);
    }

    public function downloadForwardingLetter(HrProfile $hr)
    {
        $this->authorizeHr($hr);
        $this->enforcePdfLimit();
        $hr->load(['agent', 'passport', 'visa', 'clearance', 'otherInfo', 'agency']);
        $data = PrintDataMapper::forHr($hr);
        GeneratedDocument::log('forwarding_letter', 'download', $hr);
        $filename = 'forwarding-letter-' . str_replace(' ', '-', strtolower($hr->full_name_en));
        return $this->pdf->generateFromView('prints.hr.forwarding-letter', $data, $filename);
    }

    public function downloadEmploymentAgreement(HrProfile $hr)
    {
        $this->authorizeHr($hr);
        $this->enforcePdfLimit();
        $hr->load(['agent', 'passport', 'visa', 'clearance', 'otherInfo', 'agency']);
        $data = PrintDataMapper::forHr($hr);
        GeneratedDocument::log('employment_agreement', 'download', $hr);
        $filename = 'employment-agreement-' . str_replace(' ', '-', strtolower($hr->full_name_en));
        return $this->pdf->generateFromView('prints.hr.employment-agreement', $data, $filename);
    }

    public function downloadChecklist(HrProfile $hr)
    {
        $this->authorizeHr($hr);
        $this->enforcePdfLimit();
        $hr->load(['agent', 'passport', 'visa', 'clearance', 'otherInfo', 'agency']);
        $data = PrintDataMapper::forHr($hr);
        GeneratedDocument::log('checklist', 'download', $hr);
        $filename = 'checklist-' . str_replace(' ', '-', strtolower($hr->full_name_en));
        return $this->pdf->generateFromView('prints.hr.checklist', $data, $filename);
    }

    public function downloadFullFile(HrProfile $hr)
    {
        $this->authorizeHr($hr);
        $this->enforcePdfLimit();
        $hr->load(['agent', 'passport', 'visa', 'clearance', 'otherInfo', 'agency']);
        $data = PrintDataMapper::forHr($hr);
        GeneratedDocument::log('full_file', 'download', $hr);
        $filename = 'full-file-' . str_replace(' ', '-', strtolower($hr->full_name_en));
        return $this->pdf->generateMultiPage([
            'prints.hr.application',
            'prints.hr.forwarding-letter',
            'prints.hr.employment-agreement',
            'prints.hr.checklist',
        ], $data, $filename);
    }

    public function downloadEmbassyList(EmbassyList $embassyList)
    {
        $this->authorize('view', $embassyList);
        $this->enforcePdfLimit();

        $embassyList->load(['agency', 'createdBy']);
        $data = PrintDataMapper::forEmbassyList($embassyList);
        GeneratedDocument::log('embassy_list', 'download', null, $embassyList);

        $filename = 'embassy-list-' . $embassyList->list_no;
        return $this->pdf->generateFromView('prints.embassy-list', $data, $filename);
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────────────

    private function authorizeHr(HrProfile $hr): void
    {
        $this->authorize('view', $hr);
    }

    private function enforcePdfLimit(): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) return;

        $subscription = $user->agency?->activeSubscription;
        if (! $subscription || ! $subscription->isActive()) {
            abort(redirect()->route('subscription.expired'));
        }

        $limit = $subscription->plan->max_pdf_monthly ?? 0;
        if ($limit >= 9999) return;

        $used = GeneratedDocument::where('agency_id', $user->agency_id)
            ->where('action', 'download')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        if ($used >= $limit) {
            abort(403, "Monthly PDF download limit ({$limit}) reached. Upgrade your plan or wait until next month.");
        }
    }
}
