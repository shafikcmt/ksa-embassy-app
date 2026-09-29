<?php

namespace App\Http\Requests;

use App\Models\Stamping;
use App\Models\VisaStamping;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the Visa Stamping modal (store + update). Tenancy is enforced
 * by the route middleware + controller; every DB-backed rule here is scoped to
 * the signed-in user's agency. A passport may appear once per agency (ignoring
 * soft-deleted rows and the row being edited).
 */
class VisaStampingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('passport_number')) {
            $this->merge(['passport_number' => strtoupper(trim((string) $this->input('passport_number')))]);
        }
    }

    public function rules(): array
    {
        $agencyId = (int) $this->user()->agency_id;
        $current  = $this->route('visaStamping');
        $ignoreId = $current instanceof VisaStamping ? $current->id : null;

        return [
            'full_name'          => ['required', 'string', 'max:100'],
            'passport_number'    => [
                'required', 'string', 'regex:/^[A-Z0-9]{5,20}$/',
                Rule::unique('stampings', 'passport_no')
                    ->where('agency_id', $agencyId)
                    ->whereNull('deleted_at')
                    ->ignore($ignoreId),
            ],
            'stamping_date'      => ['required', 'date', 'before_or_equal:today'],
            'status'             => ['required', Rule::in(array_keys(Stamping::STATUSES))],
            'agent_id'           => ['nullable', 'integer', Rule::exists('agents', 'id')->where('agency_id', $agencyId)],
            'reference'          => ['nullable', 'string', 'max:255'],
            'visa_number'        => ['nullable', 'string', 'max:100'],
            'id_number'          => ['nullable', 'string', 'max:100'],
            'father_name'        => ['nullable', 'string', 'max:100'],
            'mother_name'        => ['nullable', 'string', 'max:100'],
            'date_of_birth'      => ['nullable', 'date', 'before:today'],
            'mofa_number'        => ['nullable', 'string', 'max:100'],
            'mofa_date'          => ['nullable', 'date'],
            'issued_visa_number' => ['nullable', 'string', 'max:100'],
            'issued_date'        => ['nullable', 'date'],
            // Only compare when an issue date is present (after:<field> on an empty field misfires).
            'expiry_date'        => array_merge(['nullable', 'date'], $this->filled('issued_date') ? ['after:issued_date'] : []),
            'remarks'            => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'           => 'Full name is required',
            'passport_number.required'     => 'Passport number is required',
            'passport_number.regex'        => 'Passport number is invalid (5–20 letters/digits, no spaces)',
            'passport_number.unique'       => 'Passport number already exists',
            'stamping_date.required'       => 'Stamping date is required',
            'stamping_date.before_or_equal'=> 'Date cannot be in the future',
            'status.required'              => 'Status is required',
            'agent_id.exists'              => 'Choose an agent from your agency',
            'date_of_birth.before'         => 'Date of birth must be before today',
            'expiry_date.after'            => 'Expiry date must be after the issue date',
        ];
    }
}
