<?php

namespace App\Http\Requests;

use App\Models\Medical;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the ERP Medical Entry form. baseRules()/baseMessages() are
 * also used by MedicalController's CSV import, so manual Add and import can
 * never drift apart. Tenancy is enforced by the route middleware + controller.
 *
 * A passport may repeat within an agency (medical renewals) — the controller
 * warns and asks for confirmation instead of rejecting.
 */
class MedicalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::baseRules();
    }

    public function messages(): array
    {
        return self::baseMessages();
    }

    public static function baseRules(): array
    {
        return [
            'full_name'           => ['required', 'string', 'max:255'],
            'father_name'         => ['required', 'string', 'max:255'],
            'passport_no'         => ['required', 'string', 'regex:/^[A-Za-z0-9]{5,20}$/'],
            'date_of_birth'       => ['required', 'date', 'before:today'],
            'medical_center_name' => ['required', 'string', 'max:255'],
            'country'             => ['required', 'string', 'max:100'],
            'medical_code'        => ['nullable', 'string', 'max:100'],
            'mobile_no'           => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'medical_issue_date'  => ['required', 'date'],
            'medical_expire_date' => ['required', 'date', 'after:medical_issue_date'],
            'medical_status'      => ['required', Rule::in(array_keys(Medical::MEDICAL_STATUSES))],
            'reference'           => ['nullable', 'string', 'max:255'],
            'remarks'             => ['nullable', 'string', 'max:1000'],
        ];
    }

    public static function baseMessages(): array
    {
        return [
            'passport_no.regex'         => 'Passport number is invalid (5–20 letters/digits, no spaces).',
            'date_of_birth.before'      => 'Date of birth must be before today.',
            'medical_expire_date.after' => 'Medical expiry date must be after the issue date.',
            'mobile_no.regex'           => 'Mobile number may contain only digits, spaces, +, - and brackets.',
        ];
    }

    public function attributes(): array
    {
        return [
            'medical_center_name' => 'medical center name',
            'medical_code'        => 'code no',
            'medical_issue_date'  => 'medical issue date',
            'medical_expire_date' => 'medical expiry date',
            'medical_status'      => 'medical status',
            'passport_no'         => 'passport no',
        ];
    }
}
