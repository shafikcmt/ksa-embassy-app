<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = auth()->user()->agency_id;

        return [
            'name'    => 'required|string|max:255',
            'email'   => [
                'nullable', 'email', 'max:255',
                \Illuminate\Validation\Rule::unique('agents')->where('agency_id', $agencyId),
            ],
            'phone'   => 'required|string|max:30',
            'address' => 'required|string|max:500',
            'status'  => 'required|in:active,inactive',
            'notes'   => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already used by another agent in your agency.',
        ];
    }
}
