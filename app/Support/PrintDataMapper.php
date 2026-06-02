<?php

namespace App\Support;

use App\Models\EmbassyList;
use App\Models\HrProfile;

class PrintDataMapper
{
    public static function forHr(HrProfile $hr): array
    {
        $passport  = $hr->passport;
        $visa      = $hr->visa;
        $clearance = $hr->clearance;
        $other     = $hr->otherInfo;
        $agency    = $hr->agency;

        $passportYears = null;
        if ($passport?->issue_date && $passport?->expiry_date) {
            $passportYears = $passport->issue_date->diffInYears($passport->expiry_date);
        }

        // Detailed age "X years Y months Z days" for the checklist
        $ageDetail = '';
        if ($hr->date_of_birth) {
            $d = $hr->date_of_birth->diff(now());
            $ageDetail = "{$d->y} years {$d->m} months {$d->d} days";
        }

        // Application / Enjaz number shown top-right on the form and first checklist row.
        $applicationNo = $hr->mofa_new ?: ($hr->file_number && $hr->file_number !== '—' ? $hr->file_number : '');

        return [
            // ── HR Profile ──────────────────────────────────────────────
            'file_number'          => $hr->file_number ?? '—',
            'full_name_en'         => $hr->full_name_en,
            'full_name_ar'         => $hr->full_name_ar ?? '',
            'father_name'          => $hr->father_name ?? '',
            'mother_name'          => $hr->mother_name ?? '',
            'place_of_birth'       => $hr->place_of_birth ?? '',
            'nationality'          => $hr->nationality,
            'previous_nationality' => $hr->previous_nationality ?? '',
            'mofa_new'             => $hr->mofa_new ?? '',
            'mofa_old'             => $hr->mofa_old ?? '',
            'mofa'                 => $hr->mofa_new ?: ($hr->mofa_old ?? ''),
            'application_no'       => $applicationNo,
            'age_detail'           => $ageDetail,
            'sect'                 => $hr->sect ?? '',
            'home_address'         => $hr->home_address ?? '',
            'date_of_birth'        => $hr->date_of_birth?->format('d/m/Y') ?? '—',
            'age'                  => $hr->date_of_birth ? $hr->date_of_birth->age : '—',
            'gender'               => ucfirst($hr->gender),
            'religion'             => $hr->religion ?? '',
            'marital_status'       => $hr->marital_status ? ucfirst($hr->marital_status) : '',
            'occupation'           => $hr->occupation ?? '',
            'phone'                => $hr->phone ?? '',
            'email'                => $hr->email ?? '',
            'agent_name'           => $hr->agent?->name ?? '',

            // ── Passport ────────────────────────────────────────────────
            'passport_no'          => $passport?->passport_number ?? '',
            'passport_type'        => $passport?->passport_type ? ucfirst($passport->passport_type) : '',
            'passport_issue_place' => $passport?->issue_place ?? '',
            'passport_issue_date'  => $passport?->issue_date?->format('d/m/Y') ?? '',
            'passport_expiry_date' => $passport?->expiry_date?->format('d/m/Y') ?? '',
            'passport_validity_years' => $passport?->validity_years ?: ($passportYears ?? ''),
            'passport_validity_type'  => $passport?->validity_years ? $passport->validity_years . ' Years' : ($passportYears ? $passportYears . ' Years' : ''),

            // ── Visa ────────────────────────────────────────────────────
            'visa_no'              => $visa?->visa_number ?? '',
            'visa_type'            => $visa?->visa_type ?? '',
            'visa_date'            => $visa?->issue_date?->format('d/m/Y') ?? '',
            'visa_expiry_date'     => $visa?->expiry_date?->format('d/m/Y') ?? '',
            'visa_issue_place_en'  => $visa?->issue_place ?? '',
            'visa_issue_place_ar'  => $visa?->issue_place_ar ?? '',
            'sponsor_name'         => $visa?->sponsor_name ?? '',
            'sponsor_name_ar'      => $visa?->sponsor_name_ar ?? '',
            'sponsor_id'           => $visa?->sponsor_id ?? '',
            'border_number'        => $visa?->border_number ?? '',
            'profession_en'        => $visa?->profession_en ?? ($hr->occupation ?? ''),
            'profession_ar'        => $visa?->profession_ar ?? '',
            'qualification_en'     => $visa?->qualification_en ?? '',
            'qualification_ar'     => $visa?->qualification_ar ?? '',
            'travel_purpose'       => $visa?->travel_purpose ?? '',
            'musaned_no'           => $visa?->musaned_no ?? '',
            'wakala_no'            => $visa?->wakala_no ?? '',

            // ── Clearance ───────────────────────────────────────────────
            'pc_number'            => $clearance?->police_clearance_number ?? '',
            'pc_qr_code'           => $clearance?->pc_qr_code ?? '',
            'pc_display'           => $clearance?->police_clearance_number ?: ($clearance?->pc_qr_code ?? ''),
            'pc_issue_date'        => $clearance?->clearance_issue_date?->format('d/m/Y') ?? '',
            'pc_expiry_date'       => $clearance?->clearance_expiry_date?->format('d/m/Y') ?? '',
            'pc_country'           => $clearance?->clearance_country ?? '',
            'license_type'         => $clearance?->license_type ?? '',
            'fingerprint'          => $clearance?->fingerprint ?? '',
            'medical_fit'          => $clearance ? ($clearance->medical_fit ? 'Fit' : 'Unfit') : '',
            'medical_date'         => $clearance?->medical_date?->format('d/m/Y') ?? '',
            'medical_center'       => $clearance?->medical_center ?? '',

            // ── Other Info ──────────────────────────────────────────────
            'contract_period'      => $other?->contract_period ?? '',
            'salary'               => $other?->salary ? 'SAR ' . number_format($other->salary, 0) : '',
            'work_city'            => $other?->work_city ?? '',
            'destination_city'     => $other?->destination_city ?? '',
            'employer_name'        => $other?->employer_name ?? '',
            'employer_phone'       => $other?->employer_phone ?? '',
            'relationship'         => $other?->relationship ?? '',
            'carrier'              => $other?->carrier ?? '',
            'payment_mode'         => $other?->payment_mode ?? '',
            'arrival_date'         => $other?->arrival_date?->format('d/m/Y') ?? '',
            'arrival_date_ar'      => $other?->arrival_date_ar ?? '',
            'departure_date'       => $other?->departure_date?->format('d/m/Y') ?? '',
            'departure_date_ar'    => $other?->departure_date_ar ?? '',
            'business_address_en'  => $other?->business_address_en ?? '',
            'business_address_ar'  => $other?->business_address_ar ?? '',
            'kingdom_address_en'   => $other?->kingdom_address_en ?? '',
            'kingdom_address_ar'   => $other?->kingdom_address_ar ?? '',
            'duration_stay_en'     => $other?->duration_stay_en ?? ($other?->contract_period ?? ''),
            'duration_stay_ar'     => $other?->duration_stay_ar ?? '',
            'remarks'              => $other?->remarks ?? '',

            // ── Agency ──────────────────────────────────────────────────
            'agency_name'          => $agency?->name ?? '',
            'agency_rl'            => $agency?->rl_number ?? '',
            'agency_license'       => $agency?->license_number ?? '',
            'agency_address'       => $agency?->address ?? '',
            'agency_phone'         => $agency?->phone ?? '',
            'agency_email'         => $agency?->email ?? '',
            'agency_license_expiry'=> $agency?->license_expiry_date?->format('d/m/Y') ?? '',
        ];
    }

    public static function forEmbassyList(EmbassyList $list): array
    {
        $categoryOrder  = ['restamping', 'new', 'cancellation'];
        $categoryLabels = ['restamping' => 'Re-Stamping', 'new' => 'New', 'cancellation' => 'Cancellation'];

        $itemsByCategory = $list->items()
            ->orderBy('serial_no')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return [
            'list'            => $list,
            'agency'          => $list->agency,
            'categoryOrder'   => $categoryOrder,
            'categoryLabels'  => $categoryLabels,
            'itemsByCategory' => $itemsByCategory,
        ];
    }
}
