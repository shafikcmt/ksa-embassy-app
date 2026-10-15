<?php

namespace App\Services;

use App\Models\BmetEntry;
use App\Models\Delivery;
use App\Models\DoubleMofa;
use App\Models\HrProfile;
use App\Models\Medical;
use App\Models\MofaEntry;
use App\Models\VisaStamping;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Cross-module ERP data for one passport — the shared auto-fill source for the
 * Medical, MOFA, Visa Stamping and BMET modals (and, later, the HR Application).
 *
 * Looks the passport up in each ERP module (latest record per module, always
 * scoped to ONE agency) in the pipeline order Medical → MOFA → Stamping → BMET,
 * then Delivery and Double MOFA (identity + agent only). The HR profile is always
 * consulted too, but ranks LAST for every field: it fills what the ERP records
 * lack (visa / sponsor ID / MOFA no / agent …) and never overrides them.
 *
 * Every source is normalised to one canonical field vocabulary, then merged
 * per field by an explicit priority list (see FIELD_PRIORITY), so each value
 * in `merged` carries the module it came from.
 *
 * NOTE on dates: MOFA's issue/expiry are PASSPORT dates (canonical
 * passport_issue_date / passport_expiry_date); Stamping's issued/expiry are
 * VISA dates (canonical issued_date / expiry_date). They are kept apart on
 * purpose so a visa date can never land in a passport-date field.
 *
 * Read-only: never writes.
 */
class ErpPassportDataService
{
    public const MODULES = ['medical', 'mofa', 'stamping', 'bmet', 'delivery', 'double_mofa'];

    public const LABELS = [
        'medical'  => 'Medical',
        'mofa'     => 'MOFA',
        'stamping' => 'Stamping',
        'bmet'     => 'BMET',
        'delivery' => 'Delivery',
        'double_mofa' => 'Double MOFA',
        'hr'       => 'HR Profile',
    ];

    /**
     * Per module: model, its passport column, and the candidate fields that may be auto-filled when
     * blank (canonical field => model attribute). Entry-specific values (MOFA no/date, money, status,
     * dates of the module's own process, reference/agent) are deliberately never listed.
     */
    public const AUTOFILL = [
        'medical'     => ['model' => Medical::class,      'passport' => 'passport_no',     'map' => ['father_name' => 'father_name', 'date_of_birth' => 'date_of_birth', 'mobile_no' => 'mobile_no']],
        'mofa'        => ['model' => MofaEntry::class,    'passport' => 'passport_no',     'map' => ['father_name' => 'father_name', 'mother_name' => 'mother_name', 'date_of_birth' => 'date_of_birth', 'passport_issue_date' => 'issue_date', 'passport_expiry_date' => 'expiry_date', 'visa_number' => 'visa_number', 'id_number' => 'id_number']],
        'stamping'    => ['model' => VisaStamping::class, 'passport' => 'passport_number', 'map' => ['father_name' => 'father_name', 'mother_name' => 'mother_name', 'date_of_birth' => 'date_of_birth', 'visa_number' => 'visa_number', 'id_number' => 'id_number']],
        'bmet'        => ['model' => BmetEntry::class,    'passport' => 'passport_number', 'map' => ['father_name' => 'father_name', 'visa_number' => 'visa_number', 'id_number' => 'id_number']],
        'delivery'    => ['model' => Delivery::class,     'passport' => 'passport_no',     'map' => ['visa_number' => 'visa_serial']],
        'double_mofa' => ['model' => DoubleMofa::class,   'passport' => 'passport_no',     'map' => ['visa_number' => 'visa_serial']],
    ];

    /** Which sources may supply each canonical field, highest priority first. */
    public const FIELD_PRIORITY = [
        'full_name'            => ['medical', 'mofa', 'stamping', 'bmet', 'delivery', 'double_mofa', 'hr'],
        'father_name'          => ['medical', 'mofa', 'stamping', 'bmet', 'hr'],
        'mother_name'          => ['mofa', 'stamping', 'hr'],
        'date_of_birth'        => ['medical', 'mofa', 'stamping', 'hr'],
        'mobile_no'            => ['medical', 'hr'],
        'mofa_number'          => ['mofa', 'stamping', 'hr'],
        'mofa_date'            => ['mofa', 'stamping'],
        'visa_number'          => ['mofa', 'stamping', 'bmet', 'double_mofa', 'delivery', 'hr'],
        'id_number'            => ['mofa', 'stamping', 'bmet', 'hr'],
        'passport_issue_date'  => ['mofa', 'hr'],
        'passport_expiry_date' => ['mofa', 'hr'],
        'issued_visa_number'   => ['stamping'],
        'issued_date'          => ['stamping'],
        'expiry_date'          => ['stamping'],
        'reference'            => ['bmet', 'stamping', 'mofa', 'medical', 'delivery', 'double_mofa', 'hr'],
    ];

    /**
     * @param  array{0:string,1:int}|null  $exclude  [module, id] — the record being
     *         edited, so an entry never "links" to itself.
     */
    public function forPassport(int $agencyId, string $passport, ?array $exclude = null): array
    {
        $passport = strtoupper(trim($passport));

        $sources = array_filter([
            'medical'  => $this->medical($agencyId, $passport, $exclude),
            'mofa'     => $this->mofa($agencyId, $passport, $exclude),
            'stamping' => $this->stamping($agencyId, $passport, $exclude),
            'bmet'     => $this->bmet($agencyId, $passport, $exclude),
            'delivery' => $this->delivery($agencyId, $passport, $exclude),
            'double_mofa' => $this->doubleMofa($agencyId, $passport, $exclude),
            // Lowest priority for every field (FIELD_PRIORITY): fills gaps only.
            'hr'       => $this->hr($agencyId, $passport),
        ]);

        return [
            'passport' => $passport,
            'found'    => (bool) $sources,
            'erp'      => (bool) array_intersect(array_keys($sources), self::MODULES),
            'sources'  => $sources,
            'merged'   => $this->merge($sources),
        ];
    }

    /**
     * Server-side auto-fill: returns $data with every BLANK key in $map (canonical field => data key)
     * filled from the passport's other ERP/HR records. Never overwrites a value the user entered,
     * and never reads the record being saved ($exclude).
     */
    public function fillMissing(array $data, int $agencyId, ?string $passport, array $map, ?array $exclude = null): array
    {
        if (blank($passport)) {
            return $data;
        }
        $merged = $this->forPassport($agencyId, $passport, $exclude)['merged'];
        foreach ($map as $field => $key) {
            if (blank($data[$key] ?? null) && isset($merged[$field])) {
                $data[$key] = $merged[$field]['value'];
            }
        }

        return $data;
    }

    /** fillMissing() with a module's own field map (see AUTOFILL); $excludeId = the record being saved. */
    public function fillForModule(string $module, array $attrs, int $agencyId, string $passport, ?int $excludeId = null): array
    {
        return $this->fillMissing($attrs, $agencyId, $passport, self::AUTOFILL[$module]['map'], $excludeId ? [$module, $excludeId] : null);
    }

    private function merge(array $sources): array
    {
        $merged = [];
        foreach (self::FIELD_PRIORITY as $field => $order) {
            foreach ($order as $module) {
                $value = $sources[$module]['fields'][$field] ?? null;
                if ($value !== null && $value !== '') {
                    $merged[$field] = ['value' => $value, 'source' => $module];
                    break;
                }
            }
        }

        return $merged;
    }

    // ── Sources ───────────────────────────────────────────────────────────

    private function medical(int $agencyId, string $passport, ?array $exclude): ?array
    {
        $m = $this->latest(Medical::forAgency($agencyId)->where('passport_no', $passport), 'medical', $exclude);

        return $m ? $this->source('medical', $m, route('erp.medical.show', $m), [
            'full_name'     => $m->full_name,
            'father_name'   => $m->father_name,
            'date_of_birth' => $this->ymd($m->date_of_birth),
            'mobile_no'     => $m->mobile_no,
            'reference'     => $m->reference,
        ], [
            'Medical center' => $m->medical_center_name,
            'Status'         => $m->statusLabel(),
            'Age'            => $m->currentAge(),
            'Medical expiry' => $this->dmy($m->medical_expire_date),
        ]) : null;
    }

    private function mofa(int $agencyId, string $passport, ?array $exclude): ?array
    {
        // A passport may have several MOFA entries: newest MOFA Date wins (not the most recently edited).
        $query = MofaEntry::forAgency($agencyId)->where('passport_no', $passport)->latestMofa();
        if ($exclude && $exclude[0] === 'mofa') {
            $query->whereKeyNot($exclude[1]);
        }
        $m = $query->first();

        return $m ? $this->source('mofa', $m, route('erp.mofa.show', $m), [
            'full_name'            => $m->full_name,
            'father_name'          => $m->father_name,
            'mother_name'          => $m->mother_name,
            'date_of_birth'        => $this->ymd($m->date_of_birth),
            'mofa_number'          => $m->mofa_number,
            'mofa_date'            => $this->ymd($m->mofa_date),
            'visa_number'          => $m->visa_serial,
            'id_number'            => $m->id_number,
            'passport_issue_date'  => $this->ymd($m->issue_date),
            'passport_expiry_date' => $this->ymd($m->expiry_date),
            'reference'            => $m->reference_name,
        ], [
            'MOFA status' => $m->statusLabel(),
        ]) : null;
    }

    private function stamping(int $agencyId, string $passport, ?array $exclude): ?array
    {
        $s = $this->latest(VisaStamping::forAgency($agencyId)->where('passport_no', $passport), 'stamping', $exclude);

        return $s ? $this->source('stamping', $s, route('erp.visa-stamping.show', $s), [
            'full_name'          => $s->full_name,
            'father_name'        => $s->father_name,
            'mother_name'        => $s->mother_name,
            'date_of_birth'      => $this->ymd($s->date_of_birth),
            'mofa_number'        => $s->mofa_number,
            'mofa_date'          => $this->ymd($s->mofa_date),
            'visa_number'        => $s->visa_number,
            'id_number'          => $s->id_number,
            'issued_visa_number' => $s->issued_visa_number,
            'issued_date'        => $this->ymd($s->issued_date),
            'expiry_date'        => $this->ymd($s->expiry_date),
            'reference'          => $s->reference,
        ], [
            'Stamping status' => $s->statusLabel(),
            'Stamping date'   => $this->dmy($s->stamp_date),
            'Left day'        => $s->left_day,
        ]) : null;
    }

    private function bmet(int $agencyId, string $passport, ?array $exclude): ?array
    {
        $b = $this->latest(BmetEntry::forAgency($agencyId)->where('passport_no', $passport), 'bmet', $exclude);

        return $b ? $this->source('bmet', $b, route('erp.bmet.show', $b), [
            'full_name'   => $b->full_name,
            'father_name' => $b->father_name,
            'visa_number' => $b->visa_number,
            'id_number'   => $b->id_number,
            'reference'   => $b->reference,
        ], [
            'BMET status' => $b->statusLabel(),
            'EC number'   => $b->ec_number,
        ]) : null;
    }

    private function delivery(int $agencyId, string $passport, ?array $exclude): ?array
    {
        $d = $this->latest(Delivery::forAgency($agencyId)->where('passport_no', $passport), 'delivery', $exclude);

        return $d ? $this->source('delivery', $d, route('erp.delivery'), [
            'full_name'   => $d->full_name,
            'visa_number' => $d->visa_serial,
            'reference'   => $d->reference,
        ], [
            'Delivery date' => $this->dmy($d->delivery_date),
        ]) : null;
    }

    private function doubleMofa(int $agencyId, string $passport, ?array $exclude): ?array
    {
        $d = $this->latest(DoubleMofa::forAgency($agencyId)->where('passport_no', $passport), 'double_mofa', $exclude);

        return $d ? $this->source('double_mofa', $d, route('erp.double-mofa'), [
            'full_name'   => $d->full_name,
            'visa_number' => $d->visa_serial,
            'reference'   => $d->reference,
        ], [
            'Old MOFA no' => $d->old_mofa_number,
            'MOFA date'   => $this->dmy($d->mofa_date),
        ]) : null;
    }

    private function hr(int $agencyId, string $passport): ?array
    {
        $hr = HrProfile::forAgency($agencyId)
            ->with(['passport', 'visa', 'agent'])
            ->whereHas('passport', fn ($q) => $q->where('passport_number', $passport))
            ->latest('updated_at')
            ->first();

        return $hr ? $this->source('hr', $hr, route('hr.show', $hr), [
            'full_name'            => $hr->full_name_en,
            'father_name'          => $hr->father_name,
            'mother_name'          => $hr->mother_name,
            'date_of_birth'        => $this->ymd($hr->date_of_birth),
            'mobile_no'            => $hr->phone,
            'passport_issue_date'  => $this->ymd($hr->passport?->issue_date),
            'passport_expiry_date' => $this->ymd($hr->passport?->expiry_date),
            // HR visa block → ERP "Visa No" / "Id No" (the sponsor's ID); HR's new MOFA no; the HR agent = ERP "Reference".
            'visa_number'          => $hr->visa?->visa_number,
            'id_number'            => $hr->visa?->sponsor_id,
            'mofa_number'          => $hr->mofa_new,
            'reference'            => $hr->agent?->name,
        ], [
            'File number' => $hr->file_number,
        ]) : null;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function latest($query, string $module, ?array $exclude): ?Model
    {
        if ($exclude && $exclude[0] === $module) {
            $query->whereKeyNot($exclude[1]);
        }

        return $query->latest('updated_at')->latest('id')->first();
    }

    /** One source block: canonical fields (nulls dropped) + human-readable rows for the tag panel. */
    private function source(string $module, Model $record, string $url, array $fields, array $extra): array
    {
        $fields = array_filter($fields, fn ($v) => $v !== null && $v !== '');

        $display = [];
        foreach ($fields as $key => $value) {
            $display[] = [self::fieldLabel($key), preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) ? date('d-M-Y', strtotime($value)) : (string) $value];
        }
        foreach ($extra as $label => $value) {
            if ($value !== null && $value !== '') {
                $display[] = [$label, (string) $value];
            }
        }

        return [
            'module'     => $module,
            'label'      => self::LABELS[$module],
            'id'         => $record->getKey(),
            'url'        => $url,
            'updated_at' => $record->updated_at?->toIso8601String(),
            'fields'     => $fields,
            'display'    => $display,
        ];
    }

    public static function fieldLabel(string $key): string
    {
        return [
            'full_name' => 'Name', 'father_name' => "Father's name", 'mother_name' => "Mother's name",
            'date_of_birth' => 'Date of birth', 'mobile_no' => 'Mobile', 'mofa_number' => 'MOFA no',
            'mofa_date' => 'MOFA date', 'visa_number' => 'Visa no', 'id_number' => 'ID no',
            'passport_issue_date' => 'Passport issue', 'passport_expiry_date' => 'Passport expiry',
            'issued_visa_number' => 'Issued visa no', 'issued_date' => 'Visa issue date',
            'expiry_date' => 'Visa expiry date', 'reference' => 'Reference',
        ][$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    private function ymd(?CarbonInterface $date): ?string
    {
        return $date?->format('Y-m-d');
    }

    private function dmy(?CarbonInterface $date): ?string
    {
        return $date?->format('d-M-Y');
    }
}
