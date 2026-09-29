<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DoubleMofa;
use App\Models\ManpowerCompletion;
use App\Models\Medical;
use App\Models\MofaEntry;
use App\Models\Stamping;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cross-module "does this passport already exist in the ERP?" check — READ-ONLY.
 *
 * Returns EVERY matching row (not just the freshest, unlike the auto-fill
 * lookup) across the six ERP workflow tables that store an applicant passport:
 *   medicals · mofa_entries · double_mofas · stampings (Visa Stamping)
 *   · manpower_completions (BMET Clearance) · deliveries
 * Agent Khata / Due List / Reports carry no passport of their own (they read
 * Delivery / Double MOFA rows), so they are covered by the tables above.
 *
 * Matching is normalised (trim + case-insensitive) on BOTH sides so historical
 * rows stored with stray spaces or lowercase still match — stored data is never
 * rewritten. Tenancy: callers pass the authenticated user's int agency_id.
 */
class PassportRecordFinder
{
    /** Per-module cap so a heavily-repeated passport can't bloat the modal. */
    private const PER_MODULE_LIMIT = 10;

    public static function normalize(?string $passportNo): string
    {
        return mb_strtoupper(trim((string) $passportNo));
    }

    /**
     * @return array<int, array{module:string, name:?string, reference:?string, status:?string, date:?string}>
     */
    public function find(int $agencyId, ?string $passportNo): array
    {
        $needle = self::normalize($passportNo);
        if ($needle === '') {
            return [];
        }

        return array_merge(
            $this->rows(Medical::class, $agencyId, $needle, 'medical_issue_date', fn (Medical $r) => [
                'module'    => 'Medical',
                'name'      => $r->full_name,
                'reference' => $r->medical_code ? 'Code: ' . $r->medical_code : null,
                'status'    => $r->statusLabel(),
                'date'      => $r->medical_issue_date?->format('d M Y'),
            ]),
            $this->rows(MofaEntry::class, $agencyId, $needle, 'mofa_date', fn (MofaEntry $r) => [
                'module'    => 'MOFA Entry',
                'name'      => $r->full_name,
                'reference' => $r->mofa_number ? 'MOFA #: ' . $r->mofa_number : null,
                'status'    => 'Entered',
                'date'      => $r->mofa_date?->format('d M Y'),
            ]),
            $this->rows(DoubleMofa::class, $agencyId, $needle, 'mofa_date', fn (DoubleMofa $r) => [
                'module'    => 'Double MOFA',
                'name'      => $r->full_name,
                'reference' => $r->old_mofa_number ? 'Old MOFA #: ' . $r->old_mofa_number : null,
                'status'    => 'Billing: ' . $r->statusLabel(),
                'date'      => $r->mofa_date?->format('d M Y'),
            ]),
            $this->rows(Stamping::class, $agencyId, $needle, 'stamp_date', fn (Stamping $r) => [
                'module'    => 'Visa Stamping',
                'name'      => $r->full_name,
                'reference' => $r->visa_number ? 'Visa #: ' . $r->visa_number : null,
                'status'    => $r->statusLabel(),
                'date'      => $r->stamp_date?->format('d M Y'),
            ]),
            $this->rows(ManpowerCompletion::class, $agencyId, $needle, 'completed_date', fn (ManpowerCompletion $r) => [
                'module'    => 'BMET Clearance',
                'name'      => $r->customer_name,
                'reference' => $r->ec_number ? 'EC #: ' . $r->ec_number : null,
                'status'    => 'Completed',
                'date'      => $r->completed_date?->format('d M Y'),
            ]),
            $this->rows(Delivery::class, $agencyId, $needle, 'delivery_date', fn (Delivery $r) => [
                'module'    => 'Delivery',
                'name'      => $r->full_name,
                'reference' => $r->visa_serial ? 'Visa Serial: ' . $r->visa_serial : null,
                'status'    => $r->statusLabel(),
                'date'      => $r->delivery_date?->format('d M Y'),
            ]),
        );
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $model */
    private function rows(string $model, int $agencyId, string $needle, string $dateColumn, callable $map): array
    {
        return $model::query()
            ->where('agency_id', $agencyId)
            ->where(fn (Builder $q) => $q->whereRaw('UPPER(TRIM(passport_no)) = ?', [$needle]))
            ->orderByDesc($dateColumn)
            ->orderByDesc('id')
            ->limit(self::PER_MODULE_LIMIT)
            ->get()
            ->map($map)
            ->all();
    }
}
