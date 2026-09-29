{{--
    Shared create/edit payment-voucher wizard:
      1. Voucher header (payee + date + description)
      2. Payment details (method, cheque/bank/reference, tax, discount)
      3. Line items (dynamic rows) + review

    Expects: $voucher, $payeeTypes, $paymentMethods, $adjustTypes, $nextNumber,
             $action (form URL), $method ('POST'|'PUT').

    Live totals use the SAME integer-cent math as PaymentVoucherService (BigInt,
    half-up percent). The server always recomputes. Initial data reaches Alpine
    via <script type="application/json"> (not an attribute).
--}}
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
    $err = 'mt-1 text-xs font-medium text-rose-600';

    $oldItems = old('items');
    $items = is_array($oldItems)
        ? array_values($oldItems)
        : $voucher->items->map(fn ($i) => [
            'description' => $i->description,
            'quantity'    => $i->quantity,
            'unit_price'  => (string) $i->unit_price,
            'remarks'     => $i->remarks,
        ])->all();

    $initial = [
        'payee_name'     => old('payee_name', $voucher->payee_name),
        'payee_phone'    => old('payee_phone', $voucher->payee_phone),
        'payee_address'  => old('payee_address', $voucher->payee_address),
        'description'    => old('description', $voucher->description),
        'payment_method' => old('payment_method', $voucher->payment_method ?: 'cash'),
        'tax_type'       => old('tax_type', $voucher->tax_type ?: 'none'),
        'tax_value'      => old('tax_value', $voucher->tax_type && $voucher->tax_type !== 'none' ? (string) $voucher->tax_value : ''),
        'discount_type'  => old('discount_type', $voucher->discount_type ?: 'none'),
        'discount_value' => old('discount_value', $voucher->discount_type && $voucher->discount_type !== 'none' ? (string) $voucher->discount_value : ''),
        'items'          => $items,
    ];

    // Re-open the step that owns the first server-side error.
    $step1 = ['voucher_date', 'payee_type', 'payee_name', 'payee_phone', 'payee_address', 'payee_account', 'description'];
    $step2 = ['payment_method', 'cheque_number', 'bank_name', 'reference_number', 'tax_type', 'tax_value', 'discount_type', 'discount_value'];
    $initialStep = 1;
    if ($errors->any()) {
        $keys = collect($errors->keys());
        $initialStep = $keys->contains(fn ($k) => in_array($k, $step1)) ? 1
            : ($keys->contains(fn ($k) => in_array($k, $step2)) ? 2 : 3);
    }

    $formJson = json_encode(
        ['initial' => $initial, 'step' => $initialStep, 'searchUrl' => route('erp.invoices.search')],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
    );
@endphp
<script type="application/json" id="voucher-form-data">{!! $formJson !!}</script>

<form method="POST" action="{{ $action }}" novalidate x-data="voucherForm()" x-on:submit="onSubmit($event)">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <div class="flex items-center gap-2 font-semibold"><i class="bi bi-exclamation-circle-fill"></i> Please fix the highlighted fields.</div>
            <ul class="mt-1 list-inside list-disc text-xs">
                @foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Stepper --}}
    <ol class="mb-5 grid grid-cols-3 gap-2">
        @foreach([1 => ['Payee & voucher', 'bi-person-vcard'], 2 => ['Payment details', 'bi-credit-card'], 3 => ['Items & review', 'bi-list-check']] as $n => [$label, $icon])
            <li>
                <button type="button" x-on:click="go({{ $n }})"
                        class="flex w-full items-center gap-2 rounded-xl border px-3 py-2.5 text-left transition"
                        x-bind:class="step === {{ $n }} ? 'border-brand-300 bg-brand-50 ring-2 ring-brand-100' : (step > {{ $n }} ? 'border-emerald-200 bg-white' : 'border-slate-200 bg-white hover:bg-slate-50')">
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-xs font-bold"
                          x-bind:class="step === {{ $n }} ? 'bg-brand-600 text-white' : (step > {{ $n }} ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500')">
                        <i class="bi" x-bind:class="step > {{ $n }} ? 'bi-check-lg' : '{{ $icon }}'"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[0.65rem] font-semibold uppercase tracking-wide text-slate-400">Step {{ $n }}</span>
                        <span class="block truncate text-sm font-semibold text-slate-800">{{ $label }}</span>
                    </span>
                </button>
            </li>
        @endforeach
    </ol>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">

            {{-- ── Step 1: Payee & voucher ── --}}
            <section x-show="step === 1" class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-person-vcard text-brand-600"></i> Payee &amp; voucher</h2>

                {{-- Optional: pick an invoice to auto-fill payee + description (nothing is linked/stored) --}}
                <div class="mb-5 rounded-xl border border-brand-100 bg-brand-50/60 p-4" x-on:click.outside="results = []">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <div class="text-sm font-semibold text-brand-900"><i class="bi bi-link-45deg"></i> Link invoice <span class="font-normal text-brand-700/70">(optional — auto-fills payee)</span></div>
                        <template x-if="picked">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                <i class="bi bi-check-circle-fill"></i> <span x-text="picked"></span>
                                <button type="button" x-on:click="clearInvoice()" class="text-slate-400 hover:text-rose-600" title="Clear"><i class="bi bi-x-lg"></i></button>
                            </span>
                        </template>
                    </div>
                    <div class="relative">
                        <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                        <input type="text" x-model="invoiceQ" x-on:input.debounce.300ms="searchInvoices()" x-on:keydown.escape="results = []"
                               placeholder="Type invoice number or customer / agent name…" autocomplete="off" class="{{ $inp }} pl-8">
                        <div x-show="results.length" x-cloak class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                            <template x-for="inv in results" x-bind:key="inv.id">
                                <button type="button" x-on:click="pickInvoice(inv)" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-brand-50">
                                    <span class="min-w-0">
                                        <span class="block font-mono text-sm font-semibold text-slate-800" x-text="inv.invoice_number"></span>
                                        <span class="block truncate text-xs text-slate-500" x-text="(inv.bill_to_name || '—') + ' · ' + inv.status"></span>
                                    </span>
                                    <span class="shrink-0 text-xs font-semibold text-slate-700" x-text="inv.total"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="searched && !results.length && invoiceQ.trim().length >= 2" x-cloak class="mt-1 text-xs text-slate-500">No matching invoices.</p>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="{{ $lbl }}">Voucher no.</label>
                        <div class="flex h-[38px] items-center rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 font-mono text-sm font-semibold text-slate-700">{{ $nextNumber }}</div>
                        @if(! $voucher->exists)<p class="mt-1 text-[0.7rem] text-slate-400">Assigned automatically on save.</p>@endif
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Voucher date <span class="text-rose-500">*</span></label>
                        <input type="date" name="voucher_date" x-ref="voucherDate" value="{{ old('voucher_date', optional($voucher->voucher_date)->format('Y-m-d')) }}" class="{{ $inp }}">
                        @error('voucher_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Payee type <span class="text-rose-500">*</span></label>
                        <select name="payee_type" class="{{ $inp }}">
                            @foreach($payeeTypes as $k => $v)<option value="{{ $k }}" @selected(old('payee_type', $voucher->payee_type) === $k)>{{ $v }}</option>@endforeach
                        </select>
                        @error('payee_type')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $lbl }}">Payee name <span class="text-rose-500">*</span></label>
                        <input type="text" name="payee_name" x-model="payee.name" maxlength="255" placeholder="Party / provider / vendor" class="{{ $inp }}">
                        @error('payee_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Phone</label>
                        <input type="text" name="payee_phone" x-model="payee.phone" maxlength="50" class="{{ $inp }}">
                        @error('payee_phone')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Account (bank / bKash / Nagad no.)</label>
                        <input type="text" name="payee_account" maxlength="100" value="{{ old('payee_account', $voucher->payee_account) }}" class="{{ $inp }}">
                        @error('payee_account')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Address</label>
                        <input type="text" name="payee_address" x-model="payee.address" maxlength="1000" class="{{ $inp }}">
                        @error('payee_address')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label class="{{ $lbl }}">Description / purpose <span class="text-rose-500">*</span></label>
                    <textarea name="description" x-model="description" rows="2" maxlength="2000" placeholder="e.g. Manpower clearance payment for September batch" class="{{ $inp }}"></textarea>
                    @error('description')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- ── Step 2: Payment details ── --}}
            <section x-show="step === 2" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-credit-card text-brand-600"></i> Payment details</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $lbl }}">Payment method <span class="text-rose-500">*</span></label>
                        <select name="payment_method" x-model="paymentMethod" class="{{ $inp }}">
                            @foreach($paymentMethods as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                        @error('payment_method')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="paymentMethod === 'cheque'">
                        <label class="{{ $lbl }}">Cheque number <span class="text-rose-500">*</span></label>
                        <input type="text" name="cheque_number" maxlength="50" value="{{ old('cheque_number', $voucher->cheque_number) }}" class="{{ $inp }}">
                        @error('cheque_number')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="paymentMethod === 'cheque' || paymentMethod === 'bank_transfer'">
                        <label class="{{ $lbl }}">Bank name</label>
                        <input type="text" name="bank_name" maxlength="100" value="{{ old('bank_name', $voucher->bank_name) }}" class="{{ $inp }}">
                        @error('bank_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Reference no. (TrxID, slip…)</label>
                        <input type="text" name="reference_number" maxlength="100" value="{{ old('reference_number', $voucher->reference_number) }}" class="{{ $inp }}">
                        @error('reference_number')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-5 grid gap-5 border-t border-slate-100 pt-4 sm:grid-cols-2">
                    @foreach(['tax' => 'Tax', 'discount' => 'Discount'] as $k => $title)
                        <div>
                            <label class="{{ $lbl }}">{{ $title }}</label>
                            <div class="grid grid-cols-3 gap-1 rounded-lg bg-slate-100 p-1">
                                @foreach(['none' => 'None', 'percent' => '%', 'fixed' => 'Fixed'] as $type => $typeLabel)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="{{ $k }}_type" value="{{ $type }}" x-model="{{ $k }}Type" class="peer sr-only">
                                        <span class="block rounded-md px-2 py-1.5 text-center text-xs font-semibold text-slate-500 transition peer-checked:bg-white peer-checked:text-brand-700 peer-checked:shadow-sm">{{ $typeLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="relative mt-2" x-show="{{ $k }}Type !== 'none'">
                                <input type="text" inputmode="decimal" name="{{ $k }}_value" x-model="{{ $k }}Value"
                                       x-bind:disabled="{{ $k }}Type === 'none'"
                                       x-bind:placeholder="{{ $k }}Type === 'percent' ? 'e.g. 5' : '0.00'" class="{{ $inp }} pr-12 text-right">
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400" x-text="{{ $k }}Type === 'percent' ? '%' : 'BDT'"></span>
                            </div>
                            @error($k . '_value')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 border-t border-slate-100 pt-4">
                    <label class="{{ $lbl }}">Internal notes</label>
                    <textarea name="notes" rows="2" maxlength="2000" class="{{ $inp }}">{{ old('notes', $voucher->notes) }}</textarea>
                    @error('notes')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- ── Step 3: Items & review ── --}}
            <section x-show="step === 3" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-list-check text-brand-600"></i> Line items</h2>
                    <span class="text-xs text-slate-400"><span x-text="items.length"></span> / 100</span>
                </div>
                @error('items')<p class="{{ $err }} mb-3">{{ $message }}</p>@enderror

                <div class="space-y-3">
                    <template x-for="(row, i) in items" x-bind:key="row.key">
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-500">Item <span x-text="i + 1"></span></span>
                                <button type="button" x-on:click="removeRow(i)" x-show="items.length > 1" title="Remove item"
                                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"><i class="bi bi-trash"></i> Remove</button>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-12">
                                <div class="sm:col-span-6">
                                    <label class="{{ $lbl }}">Description <span class="text-rose-500">*</span></label>
                                    <input type="text" x-bind:name="`items[${i}][description]`" x-model="row.description" maxlength="255" list="voucher-item-suggestions" placeholder="e.g. Manpower Fee" class="{{ $inp }}">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="{{ $lbl }}">Qty</label>
                                    <input type="number" min="1" max="10000" step="1" x-bind:name="`items[${i}][quantity]`" x-model="row.quantity" class="{{ $inp }} text-right">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="{{ $lbl }}">Unit price <span class="text-rose-500">*</span></label>
                                    <input type="text" inputmode="decimal" x-bind:name="`items[${i}][unit_price]`" x-model="row.unit_price" placeholder="0.00" class="{{ $inp }} text-right">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="{{ $lbl }}">Amount</label>
                                    <div class="flex h-[38px] items-center justify-end rounded-lg bg-white px-3 text-sm font-bold text-slate-900 ring-1 ring-inset ring-slate-200" x-text="fmt(lineCents(row))"></div>
                                </div>
                                <div class="sm:col-span-12">
                                    <input type="text" x-bind:name="`items[${i}][remarks]`" x-model="row.remarks" maxlength="500" placeholder="Remarks (optional)" class="{{ $inp }}">
                                </div>
                            </div>
                            <p class="mt-1 text-xs font-medium text-rose-600" x-show="rowError(row)" x-text="rowError(row)"></p>
                        </div>
                    </template>
                </div>
                <datalist id="voucher-item-suggestions">
                    <option value="Manpower Fee"></option><option value="Processing"></option><option value="Delivery Charge"></option>
                    <option value="Medical Fee"></option><option value="Air Ticket"></option><option value="Commission"></option>
                </datalist>

                <button type="button" x-on:click="addRow()" x-bind:disabled="items.length >= 100"
                        class="mt-3 inline-flex w-full items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-300 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 disabled:opacity-50">
                    <i class="bi bi-plus-circle"></i> Add another item
                </button>
            </section>

            {{-- Wizard navigation --}}
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <button type="button" x-show="step > 1" x-on:click="go(step - 1)"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><i class="bi bi-arrow-left"></i> Back</button>
                    <a x-show="step === 1" href="{{ $voucher->exists ? route('erp.payment-vouchers.show', $voucher) : route('erp.payment-vouchers.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</a>
                </div>
                <div>
                    <button type="button" x-show="step < 3" x-on:click="go(step + 1)"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Next <i class="bi bi-arrow-right"></i></button>
                    <button type="submit" x-show="step === 3"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-brand-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-save"></i> Save draft</button>
                </div>
            </div>
            <p class="text-right text-xs text-slate-400" x-show="step === 3">Saved as a draft. An admin approves it, then marks it paid — after that it is locked.</p>
        </div>

        {{-- Live totals --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="bg-gradient-to-br from-[#1a1f2e] via-slate-800 to-brand-900 px-5 py-4 text-white">
                    <div class="text-[0.65rem] font-semibold uppercase tracking-widest text-white/60">Voucher total (pay out)</div>
                    <div class="mt-1 text-2xl font-bold" x-text="fmt(totals().total)"></div>
                    <div class="mt-1 font-mono text-xs text-white/60">{{ $nextNumber }}</div>
                </div>
                <dl class="space-y-2 px-5 py-4 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal (<span x-text="items.length"></span> item<span x-show="items.length !== 1">s</span>)</dt><dd class="font-semibold text-slate-800" x-text="fmt(totals().subtotal)"></dd></div>
                    <div class="flex justify-between" x-show="taxType !== 'none'"><dt class="text-slate-500">Tax <span x-show="taxType === 'percent'" x-text="'(' + (taxValue || 0) + '%)'"></span></dt><dd class="font-semibold text-slate-800" x-text="'+ ' + fmt(totals().tax)"></dd></div>
                    <div class="flex justify-between" x-show="discountType !== 'none'"><dt class="text-slate-500">Discount <span x-show="discountType === 'percent'" x-text="'(' + (discountValue || 0) + '%)'"></span></dt><dd class="font-semibold text-emerald-700" x-text="'− ' + fmt(totals().discount)"></dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-2 text-base"><dt class="font-bold text-slate-900">Total</dt><dd class="font-bold text-slate-900" x-text="fmt(totals().total)"></dd></div>
                </dl>
                <div x-show="totals().total < 0n" class="border-t border-rose-100 bg-rose-50 px-5 py-2 text-xs font-semibold text-rose-700">Discount exceeds subtotal + tax.</div>
                <div class="border-t border-slate-100 bg-slate-50 px-5 py-3 text-[0.7rem] text-slate-500"><i class="bi bi-shield-check text-emerald-600"></i> Totals are re-calculated on the server when you save.</div>
            </div>
        </aside>
    </div>
</form>

@push('scripts')
<script>
function voucherForm() {
    const data = JSON.parse(document.getElementById('voucher-form-data').textContent);
    const init = data.initial;
    let seq = 0;
    const newRow = (r = {}) => ({
        key: ++seq,
        description: r.description ?? '',
        quantity: r.quantity ?? 1,
        unit_price: r.unit_price ?? '',
        remarks: r.remarks ?? '',
    });
    const MONEY = /^\d+(\.\d{1,2})?$/;

    return {
        step: data.step || 1,
        payee: { name: init.payee_name ?? '', phone: init.payee_phone ?? '', address: init.payee_address ?? '' },
        description: init.description ?? '',
        invoiceQ: '', results: [], searched: false, picked: '',
        paymentMethod: init.payment_method,
        taxType: init.tax_type, taxValue: init.tax_value ?? '',
        discountType: init.discount_type, discountValue: init.discount_value ?? '',
        items: (init.items && init.items.length ? init.items : [{}]).map(newRow),

        // ── integer-cent math (mirrors PaymentVoucherService) ──
        toCents(v) {
            const s = String(v ?? '').replace(/[,\s]/g, '');
            if (s === '') return 0n;
            if (!MONEY.test(s)) return null;
            const [w, f = ''] = s.split('.');
            return BigInt(w) * 100n + BigInt((f + '00').slice(0, 2));
        },
        safeCents(v) { return this.toCents(v) ?? 0n; },
        qty(row) { const q = parseInt(row.quantity, 10); return Number.isFinite(q) && q > 0 ? BigInt(q) : 0n; },
        lineCents(row) { return this.qty(row) * this.safeCents(row.unit_price); },
        totals() {
            const subtotal = this.items.reduce((s, r) => s + this.lineCents(r), 0n);
            const adj = (type, val) => type === 'percent' ? (subtotal * this.safeCents(val) + 5000n) / 10000n
                                     : type === 'fixed'   ? this.safeCents(val) : 0n;
            const tax = adj(this.taxType, this.taxValue);
            const discount = adj(this.discountType, this.discountValue);
            return { subtotal, tax, discount, total: subtotal + tax - discount };
        },
        fmt(c) {
            const neg = c < 0n; if (neg) c = -c;
            const whole = (c / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return (neg ? '−' : '') + 'BDT ' + whole + '.' + (c % 100n).toString().padStart(2, '0');
        },

        // ── invoice auto-fill (agency-scoped JSON lookup; nothing is stored) ──
        async searchInvoices() {
            const q = this.invoiceQ.trim();
            this.searched = false;
            if (q.length < 2) { this.results = []; return; }
            try {
                const res = await fetch(data.searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error(res.status);
                if (q === this.invoiceQ.trim()) { this.results = await res.json(); this.searched = true; }
            } catch (e) { this.results = []; }
        },
        pickInvoice(inv) {
            this.payee = { name: inv.bill_to_name || '', phone: inv.bill_to_phone || '', address: inv.bill_to_address || '' };
            this.description = 'Payment for Invoice ' + inv.invoice_number;
            this.picked = inv.invoice_number;
            this.invoiceQ = ''; this.results = [];
        },
        clearInvoice() {
            this.payee = { name: '', phone: '', address: '' };
            this.description = '';
            this.picked = '';
        },

        addRow() { if (this.items.length < 100) this.items.push(newRow()); },
        removeRow(i) { if (this.items.length > 1) this.items.splice(i, 1); },
        rowError(row) {
            if (row.unit_price !== '' && this.toCents(row.unit_price) === null) return 'Unit price must be a number with up to 2 decimals.';
            if (row.quantity !== '' && this.qty(row) === 0n) return 'Quantity must be at least 1.';
            return '';
        },

        stepError(n) {
            if (n === 1) {
                if (!this.$refs.voucherDate.value) return 'Voucher date is required.';
                if (!this.payee.name.trim()) return 'Payee name is required.';
                if (!this.description.trim()) return 'Description is required.';
            }
            if (n === 2) {
                for (const [t, v, name] of [[this.taxType, this.taxValue, 'Tax'], [this.discountType, this.discountValue, 'Discount']]) {
                    if (t === 'none') continue;
                    const c = this.toCents(v);
                    if (c === null || String(v).trim() === '') return name + ' must be a number with up to 2 decimals.';
                    if (t === 'percent' && c > 10000n) return name + ' percentage cannot exceed 100%.';
                }
            }
            if (n === 3) {
                if (this.items.some(r => !r.description.trim())) return 'Every item needs a description.';
                if (this.items.some(r => this.rowError(r) || r.unit_price === '')) return 'Check quantity and unit price on every item.';
                if (this.totals().total < 0n) return 'Discount cannot be larger than subtotal + tax.';
            }
            return '';
        },
        go(n) {
            for (let s = this.step; s < n; s++) {
                const e = this.stepError(s);
                if (e) { this.step = s; alert(e); return; }
            }
            this.step = n;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onSubmit(e) {
            for (const s of [1, 2, 3]) {
                const err = this.stepError(s);
                if (err) { e.preventDefault(); this.step = s; alert(err); return; }
            }
        },
    };
}
</script>
@endpush
