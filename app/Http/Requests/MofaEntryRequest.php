<?php

namespace App\Http\Requests;

use App\Models\MofaEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MofaEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entry = $this->route('mofa');

        return ! $entry || (int) $entry->agency_id === (int) $this->user()->agency_id;
    }

    protected function prepareForValidation(): void
    {
        $data = ['passport_number' => strtoupper(trim((string) $this->input('passport_number')))];
        foreach (['date_of_birth', 'issue_date', 'expiry_date', 'mofa_issue_date', 'mofa_expiry_date', 'mofa_date'] as $field) {
            $value = $this->input($field);
            if (is_string($value) && preg_match('~^\d{2}/\d{2}/\d{4}$~', $value)) {
                $date = \DateTime::createFromFormat('!d/m/Y', $value);
                if ($date && $date->format('d/m/Y') === $value) {
                    $data[$field] = $date->format('Y-m-d');
                }
            }
        }
        // Backend twin of the form's auto-calculation: an empty MOFA Expiry becomes
        // MOFA Date + MofaEntry::MOFA_VALIDITY_DAYS (only when MOFA Date is a real date).
        $mofaDate = $data['mofa_date'] ?? $this->input('mofa_date');
        if (blank($this->input('mofa_expiry_date')) && is_string($mofaDate) && $this->isYmd($mofaDate)) {
            $data['mofa_expiry_date'] = MofaEntry::mofaExpiryFor($mofaDate);
        }
        $this->merge($data);
    }

    private function isYmd(string $value): bool
    {
        $date = \DateTime::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value;
    }

    /**
     * "Expiry after MOFA Date" applies to new entries and to edits that change
     * either date — an untouched legacy record must still save as-is.
     */
    private function mofaDatesChanged(): bool
    {
        $entry = $this->route('mofa');
        if (! $entry) {
            return true;
        }

        return $this->input('mofa_date') !== $entry->mofa_date?->format('Y-m-d')
            || $this->input('mofa_expiry_date') !== $entry->mofa_expiry_date?->format('Y-m-d');
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:100'], 'father_name' => ['required', 'string', 'max:100'], 'mother_name' => ['required', 'string', 'max:100'],
            'passport_number' => ['required', 'string', 'max:100', Rule::unique('mofa_entries', 'passport_no')->where('agency_id', $this->user()->agency_id)->whereNull('deleted_at')->ignore($this->route('mofa')?->id)],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today'],
            'issue_date' => ['required', 'date_format:Y-m-d'], 'expiry_date' => ['required', 'date_format:Y-m-d', 'after:issue_date'],
            // MOFA Issue Date is no longer on the form; kept optional so legacy values still round-trip on edit.
            'mofa_issue_date' => ['nullable', 'date_format:Y-m-d'],
            'mofa_expiry_date' => array_merge(['nullable', 'required_without:mofa_date', 'date_format:Y-m-d'],
                $this->filled('mofa_date') && $this->mofaDatesChanged() ? ['after:mofa_date'] : []),
            'mofa_date' => ['nullable', 'date_format:Y-m-d'],
            'visa_number' => ['nullable', 'string', 'max:100'], 'id_number' => ['nullable', 'string', 'max:100'], 'mofa_number' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'], 'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['passport_number.unique' => 'Passport number already exists.'];
    }
}
