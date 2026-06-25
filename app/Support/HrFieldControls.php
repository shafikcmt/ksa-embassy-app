<?php

namespace App\Support;

use App\Models\Setting;

/**
 * HR form field visibility controls.
 *
 * Each form field has a simple Active / Inactive status:
 *   - Active   → the field is shown on the HR form.
 *   - Inactive → the field is hidden from the HR form.
 *
 * Backend-required fields (mirrored from {@see HrProfileRules}) are LOCKED — they
 * are always Active and cannot be disabled, so form submission / validation never
 * breaks. Only optional fields are toggleable.
 *
 * Resolution order for an agency form (most specific wins):
 *   required field              → always Active
 *   agency override (Setting)   → per-agency choice, managed by Agency Admin
 *   global default (Setting)    → managed by Super Admin
 *   fallback                    → Active
 *
 * Stored with the existing key/value {@see Setting} store (no migration) under the
 * key below — a JSON map of {field_key: bool}. agency_id scopes the override; a
 * null agency_id holds the Super Admin global default.
 */
class HrFieldControls
{
    public const SETTING_KEY = 'hr_field_controls';

    /**
     * Every controllable field, in form order, grouped by section.
     * 'required' fields are locked Active.
     *
     * @return array<int, array{key:string, label:string, section:string, required:bool}>
     */
    public static function definitions(): array
    {
        return [
            // ── Personal Info ──────────────────────────────────────────
            ['key' => 'full_name_en',         'label' => 'Name',                 'section' => 'Personal Info', 'required' => true],
            ['key' => 'father_name',          'label' => 'Father Name',          'section' => 'Personal Info', 'required' => false],
            ['key' => 'mother_name',          'label' => 'Mother Name',          'section' => 'Personal Info', 'required' => false],
            ['key' => 'date_of_birth',        'label' => 'Date of Birth',        'section' => 'Personal Info', 'required' => true],
            ['key' => 'place_of_birth',       'label' => 'Place of Birth',       'section' => 'Personal Info', 'required' => true],
            ['key' => 'mofa',                 'label' => 'MOFA Application ID',   'section' => 'Personal Info', 'required' => true],
            ['key' => 'mofa_old',             'label' => 'Old MOFA',             'section' => 'Personal Info', 'required' => false],
            ['key' => 'previous_nationality', 'label' => 'Previous Nationality', 'section' => 'Personal Info', 'required' => false],
            ['key' => 'nationality',          'label' => 'Present Nationality',  'section' => 'Personal Info', 'required' => true],
            ['key' => 'gender',               'label' => 'Sex',                  'section' => 'Personal Info', 'required' => true],
            ['key' => 'marital_status',       'label' => 'Marital Status',       'section' => 'Personal Info', 'required' => true],
            ['key' => 'sect',                 'label' => 'Sect',                 'section' => 'Personal Info', 'required' => false],
            ['key' => 'religion',             'label' => 'Religion',             'section' => 'Personal Info', 'required' => true],
            ['key' => 'home_address',         'label' => 'Home Address & Phone', 'section' => 'Personal Info', 'required' => false],

            // ── Passport Info ──────────────────────────────────────────
            ['key' => 'passport_issue_place',    'label' => 'Passport Issue Place',   'section' => 'Passport Info', 'required' => false],
            ['key' => 'passport_number',         'label' => 'Passport No',            'section' => 'Passport Info', 'required' => true],
            ['key' => 'passport_issue_date',     'label' => 'Passport Issue Date',    'section' => 'Passport Info', 'required' => true],
            ['key' => 'passport_validity_years', 'label' => 'Passport Validity',      'section' => 'Passport Info', 'required' => false],
            ['key' => 'passport_expiry_date',    'label' => 'Passport Validity Date', 'section' => 'Passport Info', 'required' => true],

            // ── Visa Info ──────────────────────────────────────────────
            ['key' => 'visa_number',      'label' => 'Visa No',        'section' => 'Visa Info', 'required' => true],
            ['key' => 'visa_issue_date',  'label' => 'Visa Date',      'section' => 'Visa Info', 'required' => true],
            ['key' => 'sponsor_name',     'label' => 'Sponsor Name',   'section' => 'Visa Info', 'required' => true],
            ['key' => 'sponsor_id',       'label' => 'Sponsor ID',     'section' => 'Visa Info', 'required' => true],
            ['key' => 'visa_issue_place', 'label' => 'Place of Issue', 'section' => 'Visa Info', 'required' => false],
            ['key' => 'qualification',    'label' => 'Qualification',  'section' => 'Visa Info', 'required' => false],
            ['key' => 'profession',       'label' => 'Profession',     'section' => 'Visa Info', 'required' => true],
            ['key' => 'travel_purpose',   'label' => 'Travel Purpose', 'section' => 'Visa Info', 'required' => false],
            ['key' => 'musaned_no',       'label' => 'Musaned No',     'section' => 'Visa Info', 'required' => false],
            ['key' => 'wakala_no',        'label' => 'Wakala No',      'section' => 'Visa Info', 'required' => true],

            // ── Police Clearance & Driving License ─────────────────────
            ['key' => 'pc_qr_code',              'label' => 'P.C QRCode',        'section' => 'Police Clearance & Driving License', 'required' => false],
            ['key' => 'police_clearance_number', 'label' => 'P.C Reference No.', 'section' => 'Police Clearance & Driving License', 'required' => true],
            ['key' => 'license_type',            'label' => 'License Type',      'section' => 'Police Clearance & Driving License', 'required' => false],

            // ── Others Info ────────────────────────────────────────────
            ['key' => 'duration_stay',  'label' => 'Duration of Stay',  'section' => 'Others Info', 'required' => false],
            ['key' => 'fingerprint',    'label' => 'Fingerprint',       'section' => 'Others Info', 'required' => false],
            ['key' => 'arrival_date',   'label' => 'Date of Arrival',   'section' => 'Others Info', 'required' => false],
            ['key' => 'departure_date', 'label' => 'Date of Departure', 'section' => 'Others Info', 'required' => false],
            ['key' => 'agent_id',       'label' => 'Agent',             'section' => 'Others Info', 'required' => false],
        ];
    }

    /**
     * Definitions grouped by section label (preserves order).
     *
     * @return array<string, array<int, array{key:string, label:string, section:string, required:bool}>>
     */
    public static function grouped(): array
    {
        $out = [];
        foreach (self::definitions() as $field) {
            $out[$field['section']][] = $field;
        }
        return $out;
    }

    /**
     * Only the toggleable (non-required) field definitions.
     *
     * @return array<int, array{key:string, label:string, section:string, required:bool}>
     */
    public static function toggleable(): array
    {
        return array_values(array_filter(self::definitions(), fn ($f) => ! $f['required']));
    }

    /**
     * Effective Active/Inactive state per field key for an agency form.
     * Required keys are always true.
     *
     * @return array<string, bool>
     */
    public static function resolved(?int $agencyId): array
    {
        $global = self::rawMap(null);
        $agency = $agencyId ? self::rawMap($agencyId) : [];

        $out = [];
        foreach (self::definitions() as $field) {
            $key = $field['key'];

            if ($field['required']) {
                $out[$key] = true;
                continue;
            }

            $out[$key] = array_key_exists($key, $agency)
                ? (bool) $agency[$key]
                : (array_key_exists($key, $global) ? (bool) $global[$key] : true);
        }

        return $out;
    }

    /**
     * Stored statuses for one scope, for rendering the management UI.
     * Missing keys default to Active. When $inheritGlobal is true an agency scope
     * with no explicit value falls back to the global default (so admins see what
     * actually applies).
     *
     * @return array<string, bool>
     */
    public static function statusesForScope(?int $agencyId, bool $inheritGlobal = false): array
    {
        $global = self::rawMap(null);
        $scope  = $agencyId !== null ? self::rawMap($agencyId) : $global;

        $out = [];
        foreach (self::toggleable() as $field) {
            $key = $field['key'];

            if (array_key_exists($key, $scope)) {
                $out[$key] = (bool) $scope[$key];
            } elseif ($inheritGlobal && array_key_exists($key, $global)) {
                $out[$key] = (bool) $global[$key];
            } else {
                $out[$key] = true;
            }
        }

        return $out;
    }

    /**
     * Persist statuses for a scope. Only toggleable keys are stored; a key present
     * in $activeKeys is Active, everything else Inactive.
     *
     * @param  array<int, string>  $activeKeys
     */
    public static function save(array $activeKeys, ?int $agencyId): void
    {
        $map = [];
        foreach (self::toggleable() as $field) {
            $map[$field['key']] = in_array($field['key'], $activeKeys, true);
        }

        Setting::set(self::SETTING_KEY, json_encode($map), $agencyId);
    }

    /**
     * Raw stored map for a scope.
     *
     * @return array<string, bool>
     */
    private static function rawMap(?int $agencyId): array
    {
        $raw = Setting::get(self::SETTING_KEY, $agencyId, null);

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($decoded) ? $decoded : [];
    }
}
