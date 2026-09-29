<?php

namespace App\Http\Requests;

use App\Models\BmetEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BmetEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entry = $this->route('bmetEntry');

        return $this->user()?->agency_id && (! $entry || (int) $entry->agency_id === (int) $this->user()->agency_id);
    }

    protected function prepareForValidation(): void
    {
        $passport = $this->input('passport_number', $this->input('passport_no'));
        $data = ['passport_number' => is_string($passport) ? strtoupper(trim($passport)) : $passport];
        if (! $this->has('full_name') && $this->has('customer_name')) {
            $data['full_name'] = $this->input('customer_name');
        }
        $date = $this->input('ec_date', $this->input('completed_date'));
        if (is_string($date) && preg_match('~^\d{2}/\d{2}/\d{4}$~', $date)) {
            $parsed = \DateTime::createFromFormat('!d/m/Y', $date);
            if ($parsed && $parsed->format('d/m/Y') === $date) {
                $date = $parsed->format('Y-m-d');
            }
        }
        $data['ec_date'] = $date;
        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'passport_number' => ['required', 'string', 'max:100', Rule::unique('manpower_completions', 'passport_no')->where('agency_id', $this->user()->agency_id)->whereNull('deleted_at')->ignore($this->route('bmetEntry')?->id)],
            'visa_number' => ['nullable', 'string', 'max:100'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'ec_number' => ['nullable', 'string', 'max:100'],
            'ec_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'agent_id' => ['nullable', 'integer', Rule::exists('agents', 'id')->where('agency_id', $this->user()->agency_id)],
            'reference' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::in(array_merge(['auto'], array_keys(BmetEntry::STATUSES)))],
        ];
    }

    public function messages(): array
    {
        return ['full_name.required' => 'Passenger name is required', 'passport_number.required' => 'Passport number is required',
            'passport_number.unique' => 'Passport number already exists', 'ec_date.required' => 'Please select a valid date',
            'ec_date.date_format' => 'Please select a valid date', 'ec_date.before_or_equal' => 'Date cannot be in the future',
            'agent_id.exists' => 'Select an agent from your agency.'];
    }
}
