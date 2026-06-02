<?php

namespace App\Support;

/**
 * Shared validation rules for creating / updating an HR profile.
 *
 * Required fields mirror the embassy form reference (marked * in the UI):
 *  Name, Date of Birth, MOFA Application ID, Place of Birth, Sex, Marital Status,
 *  Religion, Present Nationality, Passport No, Passport Issue Date,
 *  Passport Validity Date, Visa No, Visa Date, Sponsor Name, Sponsor ID,
 *  Profession, Wakala No, P.C Reference No.
 *
 * Email / file_number uniqueness rules are added per-request (they differ on update).
 */
class HrProfileRules
{
    public static function rules(int $agencyId): array
    {
        return [
            // ── Personal ────────────────────────────────────────────────
            'full_name_en'         => ['required', 'string', 'max:150'],
            'full_name_ar'         => ['nullable', 'string', 'max:150'],
            'father_name'          => ['nullable', 'string', 'max:150'],
            'mother_name'          => ['nullable', 'string', 'max:150'],
            'date_of_birth'        => ['required', 'date', 'before:today'],
            'mofa_new'             => ['required', 'string', 'max:50'],
            'mofa_old'             => ['nullable', 'string', 'max:50'],
            'place_of_birth'       => ['required', 'string', 'max:150'],
            'previous_nationality' => ['nullable', 'string', 'max:100'],
            'nationality'          => ['required', 'string', 'max:100'],
            'gender'               => ['required', 'in:male,female'],
            'marital_status'       => ['required', 'in:single,married,divorced,widowed'],
            'sect'                 => ['nullable', 'string', 'max:100'],
            'religion'             => ['required', 'string', 'max:100'],
            'home_address'         => ['nullable', 'string', 'max:500'],
            'occupation'           => ['nullable', 'string', 'max:100'],
            'phone'                => ['nullable', 'string', 'max:30'],
            'agent_id'             => ['nullable', 'exists:agents,id'],
            'status'               => ['required', 'in:active,inactive,blacklisted,listed'],
            'notes'                => ['nullable', 'string'],

            // ── Passport ────────────────────────────────────────────────
            'passport_issue_place' => ['nullable', 'string', 'max:150'],
            'passport_number'      => ['required', 'string', 'max:50'],
            'passport_type'        => ['nullable', 'in:regular,diplomatic,service'],
            'passport_issue_date'  => ['required', 'date'],
            'passport_validity_years' => ['nullable', 'in:5,10'],
            'passport_expiry_date' => ['required', 'date'],

            // ── Visa ────────────────────────────────────────────────────
            'visa_number'      => ['required', 'string', 'max:50'],
            'visa_type'        => ['nullable', 'string', 'max:100'],
            'visa_issue_date'  => ['required', 'date'],
            'visa_expiry_date' => ['nullable', 'date'],
            'sponsor_name'     => ['required', 'string', 'max:150'],
            'sponsor_name_ar'  => ['nullable', 'string', 'max:150'],
            'sponsor_id'       => ['required', 'string', 'max:50'],
            'visa_issue_place'    => ['nullable', 'string', 'max:150'],
            'visa_issue_place_ar' => ['nullable', 'string', 'max:150'],
            'qualification_en' => ['nullable', 'string', 'max:100'],
            'qualification_ar' => ['nullable', 'string', 'max:100'],
            'profession_en'    => ['required', 'string', 'max:100'],
            'profession_ar'    => ['nullable', 'string', 'max:100'],
            'travel_purpose'   => ['nullable', 'string', 'max:100'],
            'musaned_no'       => ['nullable', 'string', 'max:50'],
            'wakala_no'        => ['required', 'string', 'max:50'],
            'border_number'    => ['nullable', 'string', 'max:50'],

            // ── Police Clearance & Driving License ──────────────────────
            'pc_qr_code'              => ['nullable', 'string', 'max:1000'],
            'police_clearance_number' => ['required', 'string', 'max:100'],
            'license_type'            => ['nullable', 'string', 'max:100'],

            // ── Others Info ─────────────────────────────────────────────
            'duration_stay_en'  => ['nullable', 'string', 'max:100'],
            'duration_stay_ar'  => ['nullable', 'string', 'max:100'],
            'arrival_date'      => ['nullable', 'date'],
            'arrival_date_ar'   => ['nullable', 'string', 'max:100'],
            'departure_date'    => ['nullable', 'date'],
            'departure_date_ar' => ['nullable', 'string', 'max:100'],
            'fingerprint'       => ['nullable', 'string', 'max:100'],
            'business_address_en' => ['nullable', 'string', 'max:255'],
            'business_address_ar' => ['nullable', 'string', 'max:255'],
            'kingdom_address_en'  => ['nullable', 'string', 'max:255'],
            'kingdom_address_ar'  => ['nullable', 'string', 'max:255'],

            // ── Optional Employment / Medical (legacy, kept for documents) ─
            'clearance_issue_date'  => ['nullable', 'date'],
            'clearance_expiry_date' => ['nullable', 'date'],
            'clearance_country'     => ['nullable', 'string', 'max:100'],
            'medical_fit'           => ['nullable', 'boolean'],
            'medical_date'          => ['nullable', 'date'],
            'medical_center'        => ['nullable', 'string', 'max:150'],
            'contract_period'       => ['nullable', 'string', 'max:50'],
            'salary'                => ['nullable', 'numeric', 'min:0'],
            'work_city'             => ['nullable', 'string', 'max:100'],
            'employer_name'         => ['nullable', 'string', 'max:150'],
            'employer_phone'        => ['nullable', 'string', 'max:30'],
            'remarks'               => ['nullable', 'string'],
        ];
    }

    public static function messages(): array
    {
        return [
            'mofa_new.required'        => 'MOFA Application ID (New Mofa) is required.',
            'place_of_birth.required'  => 'Place of Birth is required.',
            'profession_en.required'   => 'Profession (English) is required.',
            'wakala_no.required'       => 'Wakala No is required.',
            'police_clearance_number.required' => 'P.C Reference No. is required.',
            'passport_number.required' => 'Passport No is required.',
            'passport_expiry_date.required' => 'Passport Validity Date is required.',
            'visa_number.required'     => 'Visa No is required.',
            'visa_issue_date.required' => 'Visa Date is required.',
        ];
    }
}
