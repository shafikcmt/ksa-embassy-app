<?php

namespace App\Http\Requests;

use App\Support\HrProfileRules;
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

        return array_merge(
            HrProfileRules::rules($agencyId),
            ['email' => [
                'nullable', 'email', 'max:150',
                Rule::unique('hr_profiles')->where('agency_id', $agencyId),
            ],
            'file_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('hr_profiles')->where('agency_id', $agencyId),
            ]],
        );
    }

    public function messages(): array
    {
        return HrProfileRules::messages();
    }
}
