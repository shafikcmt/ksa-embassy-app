<?php

namespace App\Services;

use App\Models\HrProfile;
use Carbon\Carbon;

class HrErpLookupService
{
    public function __construct(private PassportRecordFinder $finder) {}

    public function search(int $agencyId, array $criteria): array
    {
        $records = $this->finder->hrRecords($agencyId, $criteria);
        usort($records, fn ($a, $b) => strcmp($b['data']['updated_at'] ?? '', $a['data']['updated_at'] ?? '')
            ?: strcmp($a['module'], $b['module']) ?: ($b['data']['id'] <=> $a['data']['id']));
        $candidates = [];
        foreach ($records as $record) {
            $r = $record['data'];
            $module = $record['module'];
            $passport = PassportRecordFinder::normalize($r['passport_no']);
            $candidates[$passport] ??= ['passport' => $passport, 'fields' => [], 'records' => [], 'hr_profiles' => []];
            $candidate = &$candidates[$passport];
            $candidate['records'][] = [
                'module' => $module, 'name' => $r['full_name'] ?? $r['customer_name'] ?? null,
                'visa_number' => $r['visa_number'] ?? null, 'visa_serial' => $r['visa_serial'] ?? null,
                'mofa_number' => $r['mofa_number'] ?? $r['old_mofa_number'] ?? null,
                'updated_at' => $r['updated_at'],
            ];
            $map = ['passport_no' => 'passport_number', 'full_name' => 'full_name_en',
                'customer_name' => 'full_name_en', 'father_name' => 'father_name', 'mother_name' => 'mother_name',
                'date_of_birth' => 'date_of_birth', 'mofa_number' => 'mofa_new', 'old_mofa_number' => 'mofa_old',
                'visa_number' => 'visa_number'];
            if ($module === 'MOFA Entry') {
                $map += ['visa_serial' => 'visa_number', 'issue_date' => 'passport_issue_date', 'expiry_date' => 'passport_expiry_date'];
            }
            if ($module === 'BMET Clearance') {
                $map['id_number'] = 'sponsor_id';
            }
            foreach ($map as $column => $field) {
                $value = $r[$column] ?? null;
                if ($value === null || trim((string) $value) === '' || isset($candidate['fields'][$field])) {
                    continue;
                }
                if (in_array($field, ['date_of_birth', 'passport_issue_date', 'passport_expiry_date'], true)) {
                    try {
                        $value = Carbon::parse($value)->format($field === 'date_of_birth' ? 'Y-m-d' : 'd-m-Y');
                    } catch (\Throwable) {
                        continue; // unparseable legacy date: skip the field, never fail the lookup
                    }
                }
                $candidate['fields'][$field] = ['value' => $field === 'passport_number' ? $passport : $value, 'source' => $module];
            }
            unset($candidate);
        }
        if ($candidates) {
            $profiles = HrProfile::query()->forAgency($agencyId)->with('passport')
                ->whereHas('passport', fn ($q) => $q->whereIn(\Illuminate\Support\Facades\DB::raw('UPPER(TRIM(passport_number))'), array_map('strval', array_keys($candidates))))
                ->get(['id', 'full_name_en']);
            foreach ($profiles as $profile) {
                $key = PassportRecordFinder::normalize($profile->passport->passport_number);
                $candidates[$key]['hr_profiles'][] = ['id' => $profile->id, 'name' => $profile->full_name_en,
                    'url' => route('hr.show', $profile)];
            }
        }
        ksort($candidates);
        return array_values($candidates);
    }

    /**
     * Visa-level details for a visa number (same visa ⇒ same sponsor, visa date, place, profession…).
     * 1) the agency's latest HR record with that visa number, 2) ERP (BMET Clearance) for the
     * sponsor ID. Keys are the HR form's input names; only non-empty values are returned. Agency-scoped.
     *
     * @return array<string, array{value: string, source: string}>
     */
    public function visaDetails(int $agencyId, string $visaNumber, ?int $excludeHrId = null): array
    {
        $needle = PassportRecordFinder::normalize($visaNumber);
        if ($needle === '') {
            return [];
        }
        $fields = [];
        $put = function (string $field, $value, string $source) use (&$fields) {
            if ($value !== null && trim((string) $value) !== '' && ! isset($fields[$field])) {
                $fields[$field] = ['value' => trim((string) $value), 'source' => $source];
            }
        };

        $visa = \App\Models\Visa::query()
            ->whereRaw('UPPER(TRIM(visa_number)) = ?', [$needle])
            ->whereHas('hrProfile', function ($q) use ($agencyId, $excludeHrId) {
                $q->forAgency($agencyId);
                if ($excludeHrId) {
                    $q->whereKeyNot($excludeHrId);
                }
            })
            ->latest('updated_at')->latest('id')->first();
        if ($visa) {
            foreach ([
                'visa_issue_date' => 'issue_date', 'sponsor_name' => 'sponsor_name', 'sponsor_name_ar' => 'sponsor_name_ar',
                'sponsor_id' => 'sponsor_id', 'visa_issue_place' => 'issue_place', 'visa_issue_place_ar' => 'issue_place_ar',
                'profession_en' => 'profession_en', 'profession_ar' => 'profession_ar',
                'qualification_en' => 'qualification_en', 'qualification_ar' => 'qualification_ar',
                'travel_purpose' => 'travel_purpose', 'wakala_no' => 'wakala_no', 'musaned_no' => 'musaned_no',
            ] as $field => $column) {
                $put($field, $visa->{$column}, 'HR Record');
            }
        }

        // ERP: only BMET's id_number is a proven sponsor ID (same rule as search()).
        if (! isset($fields['sponsor_id'])) {
            $bmet = \App\Models\ManpowerCompletion::query()->where('agency_id', $agencyId)
                ->whereRaw('UPPER(TRIM(visa_number)) = ?', [$needle])
                ->whereNotNull('id_number')->where('id_number', '!=', '')
                ->latest('updated_at')->first(['id', 'id_number']);
            $put('sponsor_id', $bmet?->id_number, 'BMET Clearance');
        }

        return $fields;
    }
}
