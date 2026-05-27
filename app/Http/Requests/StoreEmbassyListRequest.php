<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmbassyListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'list_date'             => ['required', 'date'],
            'title'                 => ['nullable', 'string', 'max:200'],
            'notes'                 => ['nullable', 'string'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.hr_profile_id' => ['required', 'integer', 'exists:hr_profiles,id'],
            'items.*.category'      => ['required', 'in:new,restamping,cancellation'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'Please select at least one HR profile.',
            'items.min'                   => 'Please select at least one HR profile.',
            'items.*.hr_profile_id.required' => 'HR profile selection is invalid.',
            'items.*.category.required'   => 'Each selected candidate must have a category assigned.',
            'items.*.category.in'         => 'Category must be New, Re-stamping, or Cancellation.',
        ];
    }
}
