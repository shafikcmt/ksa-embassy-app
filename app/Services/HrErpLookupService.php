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
}
