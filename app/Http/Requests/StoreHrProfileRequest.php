<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHrProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = auth()->user()->agency_id;

        return [
            // Personal
            'full_name_en'   => ['required', 'string', 'max:150'],
            'full_name_ar'   => ['nullable', 'string', 'max:150'],
            'nationality'    => ['required', 'string', 'max:100'],
            'date_of_birth'  => ['required', 'date', 'before:today'],
            'gender'         => ['required', 'in:male,female'],
            'religion'       => ['nullable', 'string', 'max:100'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
            'occupation'     => ['nullable', 'string', 'max:100'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'email'          => [
                'nullable', 'email', 'max:150',
                Rule::unique('hr_profiles')->where('agency_id', $agencyId),
            ],
            'agent_id'       => ['nullable', 'exists:agents,id'],
            'status'         => ['required', 'in:active,inactive,blacklisted'],
            'file_number'    => [
                'nullable', 'string', 'max:50',
                Rule::unique('hr_profiles')->where('agency_id', $agencyId),
            ],
            'notes'          => ['nullable', 'string'],

            // Passport
            'passport_number' => ['nullable', 'string', 'max:50'],
            'passport_type'   => ['nullable', 'in:regular,diplomatic,service'],
            'passport_issue_date'   => ['nullable', 'date'],
            'passport_expiry_date'  => ['nullable', 'date'],
            'passport_issue_place'  => ['nullable', 'string', 'max:150'],

            // Visa
            'visa_number'    => ['nullable', 'string', 'max:50'],
            'visa_type'      => ['nullable', 'string', 'max:100'],
            'visa_issue_date'   => ['nullable', 'date'],
            'visa_expiry_date'  => ['nullable', 'date'],
            'visa_issue_place'  => ['nullable', 'string', 'max:150'],
            'sponsor_name'   => ['nullable', 'string', 'max:150'],
            'sponsor_id'     => ['nullable', 'string', 'max:50'],
            'border_number'  => ['nullable', 'string', 'max:50'],

            // Clearance
            'police_clearance_number'  => ['nullable', 'string', 'max:100'],
            'clearance_issue_date'     => ['nullable', 'date'],
            'clearance_expiry_date'    => ['nullable', 'date'],
            'clearance_country'        => ['nullable', 'string', 'max:100'],
            'medical_fit'              => ['nullable', 'boolean'],
            'medical_date'             => ['nullable', 'date'],
            'medical_center'           => ['nullable', 'string', 'max:150'],

            // Other info
            'contract_period'  => ['nullable', 'string', 'max:50'],
            'salary'           => ['nullable', 'numeric', 'min:0'],
            'work_city'        => ['nullable', 'string', 'max:100'],
            'employer_name'    => ['nullable', 'string', 'max:150'],
            'employer_phone'   => ['nullable', 'string', 'max:30'],
            'arrival_date'     => ['nullable', 'date'],
            'departure_date'   => ['nullable', 'date'],
            'remarks'          => ['nullable', 'string'],
        ];
    }
}
