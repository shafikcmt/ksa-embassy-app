<?php

namespace App\Http\Requests;

use App\Support\HrProfileRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHrProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId  = auth()->user()->agency_id;
        $profileId = $this->route('hr')->id;

        return array_merge(
            HrProfileRules::rules($agencyId),
            ['email' => [
                'nullable', 'email', 'max:150',
                Rule::unique('hr_profiles')->where('agency_id', $agencyId)->ignore($profileId),
            ],
            'file_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('hr_profiles')->where('agency_id', $agencyId)->ignore($profileId),
            ]],
        );
    }

    public function messages(): array
    {
        return HrProfileRules::messages();
    }
}
