<?php

namespace App\Http\Requests;

use App\Models\PaymentVoucher;
use App\Services\InvoiceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create/update validation for payment vouchers. Money is validated as decimal
 * STRINGS (≤ 2 places, no sign) so it reaches PaymentVoucherService's integer-
 * cent parser without ever passing through a float. Authorization is done in
 * authorize() below via PaymentVoucherPolicy (create / update).
 */
class PaymentVoucherRequest extends FormRequest
{
    private const MONEY = '/^\d{1,12}(\.\d{1,2})?$/';
    private const PRICE = '/^\d{1,11}(\.\d{1,2})?$/';

    /** Policy check runs BEFORE validation, so another agency gets a 403, not field errors. */
    public function authorize(): bool
    {
        $voucher = $this->route('paymentVoucher');

        return $voucher instanceof PaymentVoucher
            ? $this->user()->can('update', $voucher)
            : $this->user()->can('create', PaymentVoucher::class);
    }

    public function rules(): array
    {
        return [
            'voucher_date'     => ['required', 'date'],
            'payee_type'       => ['required', Rule::in(array_keys(PaymentVoucher::PAYEE_TYPES))],
            'payee_name'       => ['required', 'string', 'max:255'],
            'payee_phone'      => ['nullable', 'string', 'max:50'],
            'payee_address'    => ['nullable', 'string', 'max:1000'],
            'payee_account'    => ['nullable', 'string', 'max:100'],
            'description'      => ['required', 'string', 'max:2000'],

            'payment_method'   => ['required', Rule::in(array_keys(PaymentVoucher::PAYMENT_METHODS))],
            'cheque_number'    => ['nullable', 'required_if:payment_method,cheque', 'string', 'max:50'],
            'bank_name'        => ['nullable', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:100'],

            'tax_type'         => ['required', Rule::in(array_keys(PaymentVoucher::ADJUST_TYPES))],
            'tax_value'        => ['exclude_if:tax_type,none', 'required', 'regex:' . self::MONEY],
            'discount_type'    => ['required', Rule::in(array_keys(PaymentVoucher::ADJUST_TYPES))],
            'discount_value'   => ['exclude_if:discount_type,none', 'required', 'regex:' . self::MONEY],
            'notes'            => ['nullable', 'string', 'max:2000'],

            'items'               => ['required', 'array', 'min:1', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity'    => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.unit_price'  => ['required', 'regex:' . self::PRICE],
            'items.*.remarks'     => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'               => 'Add at least one line item.',
            'items.*.description.required' => 'Each line needs a description.',
            'items.*.unit_price.regex'     => 'Unit price must be a number with up to 2 decimals.',
            'tax_value.regex'              => 'Tax must be a number with up to 2 decimals.',
            'discount_value.regex'         => 'Discount must be a number with up to 2 decimals.',
            'cheque_number.required_if'    => 'Cheque number is required for cheque payments.',
        ];
    }

    public function attributes(): array
    {
        return ['items.*.quantity' => 'quantity', 'items.*.unit_price' => 'unit price'];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            foreach (['tax', 'discount'] as $k) {
                $value = $this->input($k . '_value');
                if ($this->input($k . '_type') === 'percent' && is_string($value)
                    && preg_match(self::MONEY, $value) && InvoiceService::toCents($value) > 10000) {
                    $v->errors()->add($k . '_value', ucfirst($k) . ' percentage cannot exceed 100%.');
                }
            }
        }];
    }

    /** @return array{0: array, 1: array} [header data, items] */
    public function voucherData(): array
    {
        $validated = $this->validated();

        $items = array_map(fn ($i) => [
            'description' => trim($i['description']),
            'quantity'    => (int) $i['quantity'],
            'unit_price'  => $i['unit_price'],
            'remarks'     => isset($i['remarks']) && trim((string) $i['remarks']) !== '' ? trim($i['remarks']) : null,
        ], array_values($validated['items']));

        return [collect($validated)->except('items')->all(), $items];
    }
}
