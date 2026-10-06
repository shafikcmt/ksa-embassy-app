{{--
    Shared create/edit invoice wizard (3 steps: Details → Line items → Adjust & review).

    Expects: $invoice, $passengers, $agents, $currencies, $adjustTypes, $nextNumber,
             $action (form URL), $method ('POST'|'PUT').

    Totals are previewed live in the browser with the SAME integer-cent math as
    App\Services\InvoiceService (BigInt, half-up percent rounding). The server
    always recomputes; nothing here is trusted. Initial data is handed to Alpine
    via <script type="application/json"> (not an attribute) — see the
    @js-in-component-attribute pitfall.
--}}
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
    $err = 'mt-1 text-xs font-medium text-rose-600';

    $oldItems = old('items');
    $items = is_array($oldItems)
        ? array_values($oldItems)
        : $invoice->items->map(fn ($i) => [
            'hr_profile_id'  => $i->hr_profile_id,
            'passenger_name' => $i->displayName(),
            'passport_no'    => $i->displayPassport(),
            'processing_fee' => (string) $i->processing_fee,
            'mofa_fee'       => (string) $i->mofa_fee,
            'paid_amount'    => $i->paid_amount !== null ? (string) $i->paid_amount : '',
            'remarks'        => $i->remarks ?? '',
        ])->all();

    $initial = [
        'agent_id'        => old('agent_id', $invoice->agent_id),
        'bill_to_name'    => old('bill_to_name', $invoice->bill_to_name),
        'bill_to_phone'   => old('bill_to_phone', $invoice->bill_to_phone),
        'bill_to_address' => old('bill_to_address', $invoice->bill_to_address),
        'bill_to_email'   => old('bill_to_email',   $invoice->bill_to_email),
        'currency'        => old('currency', $invoice->currency ?: 'BDT'),
        'tax_type'        => old('tax_type', $invoice->tax_type ?: 'none'),
        'tax_value'       => old('tax_value', $invoice->tax_type === 'none' ? '' : (string) $invoice->tax_value),
        'discount_type'   => old('discount_type', $invoice->discount_type ?: 'none'),
        'discount_value'  => old('discount_value', $invoice->discount_type === 'none' ? '' : (string) $invoice->discount_value),
        'items'           => $items,
    ];

    // Re-open the step that owns the first server-side error.
    $initialStep = 1;
    if ($errors->any()) {
        $keys = collect($errors->keys());
        if ($keys->contains(fn ($k) => str_starts_with($k, 'items'))) $initialStep = 2;
        elseif ($keys->contains(fn ($k) => preg_match('/^(tax|discount|notes)/', $k))) $initialStep = 3;
        if ($keys->contains(fn ($k) => in_array($k, ['invoice_date', 'agent_id', 'bill_to_name', 'bill_to_phone', 'bill_to_address', 'bill_to_email', 'currency', 'status']))) $initialStep = 1;
    }
@endphp

@php
    $formJson = json_encode(
        ['initial' => $initial, 'passengers' => $passengers, 'agents' => $agents, 'symbols' => $currencies, 'step' => $initialStep],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
    );
@endphp
<script type="application/json" id="invoice-form-data">{!! $formJson !!}</script>

<form method="POST" action="{{ $action }}" novalidate x-data="invoiceForm()" x-on:submit="onSubmit($event)">
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
        @foreach([1 => ['Details', 'bi-file-earmark-text'], 2 => ['Line items', 'bi-list-ul'], 3 => ['Adjust & review', 'bi-calculator']] as $n => [$label, $icon])
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

            {{-- ── Step 1: Details ── --}}
            <section x-show="step === 1" class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-file-earmark-text text-brand-600"></i> Invoice details</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $lbl }}">Invoice no.</label>
                        <div class="flex h-[38px] items-center rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 font-mono text-sm font-semibold text-slate-700">{{ $nextNumber }}</div>
                        @if(! $invoice->exists)<p class="mt-1 text-[0.7rem] text-slate-400">Assigned automatically on save.</p>@endif
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Invoice date <span class="text-rose-500">*</span></label>
                        <input type="date" name="invoice_date" x-ref="invoiceDate" required value="{{ old('invoice_date', optional($invoice->invoice_date)->format('Y-m-d')) }}" class="{{ $inp }}">
                        @error('invoice_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-5 border-t border-slate-100 pt-4">
                    <h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Bill to</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $lbl }}">Agent (optional)</label>
                            <select name="agent_id" x-model="agentId" x-on:change="pickAgent()" class="{{ $inp }}">
                                <option value="">— No agent —</option>
                                @foreach($agents as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                            </select>
                            @error('agent_id')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Name</label>
                            <input type="text" name="bill_to_name" x-model="billTo.name" maxlength="255" placeholder="Customer / agent name" class="{{ $inp }}">
                            @error('bill_to_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Phone</label>
                            <input type="text" name="bill_to_phone" x-model="billTo.phone" maxlength="50" class="{{ $inp }}">
                            @error('bill_to_phone')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Address</label>
                            <input type="text" name="bill_to_address" x-model="billTo.address" maxlength="255" class="{{ $inp }}">
                            @error('bill_to_address')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Email</label>
                            <input type="email" name="bill_to_email" x-model="billTo.email" maxlength="255" placeholder="optional" class="{{ $inp }}">
                            @error('bill_to_email')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-3">
                    <div>
                        <label class="{{ $lbl }}">Currency <span class="text-rose-500">*</span></label>
                        <select name="currency" x-model="currency" class="{{ $inp }}">
                            @foreach($currencies as $code => $sym)<option value="{{ $code }}">{{ $code }} ({{ $sym }})</option>@endforeach
                        </select>
                        @error('currency')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- ── Step 2: Line items ── --}}
            <section x-show="step === 2" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-list-ul text-brand-600"></i> Line items</h2>
                    <span class="text-xs text-slate-400"><span x-text="items.length"></span> / 100</span>
                </div>
                @error('items')<p class="{{ $err }} mb-3">{{ $message }}</p>@enderror

                <div class="space-y-3">
                    <template x-for="(row, i) in items" x-bind:key="row.key">
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-500">Item <span x-text="i + 1"></span></span>
                                <button type="button" x-on:click="removeRow(i)" x-show="items.length > 1" title="Remove line"
                                        class="grid h-7 w-7 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"><i class="bi bi-trash"></i></button>
                            </div>

                            {{-- Passenger picker (agency-scoped list, client-side search) --}}
                            <div class="relative mb-2" x-on:click.outside="row.open = false">
                                <input type="hidden" x-bind:name="`items[${i}][hr_profile_id]`" x-bind:value="row.hr_profile_id || ''">
                                <input type="hidden" x-bind:name="`items[${i}][passenger_name]`" x-bind:value="row.passenger_name || ''">
                                <input type="hidden" x-bind:name="`items[${i}][passport_no]`" x-bind:value="row.passport_no || ''">
                                <template x-if="hasPassenger(row)">
                                    <div class="flex items-center gap-2 rounded-lg border border-brand-200 bg-white px-3 py-2 text-sm">
                                        <i class="bi bi-person-badge text-brand-600"></i>
                                        <span class="min-w-0 flex-1 truncate font-medium text-slate-800" x-text="passengerLabel(row)"></span>
                                        <button type="button" x-on:click="clearPassenger(row)" class="text-slate-400 hover:text-rose-600" title="Unlink passenger"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </template>
                                <template x-if="! hasPassenger(row)">
                                    <div>
                                        <div class="relative">
                                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                                            <input type="text" x-model="row.search" x-on:focus="row.open = true" x-on:input="row.open = true"
                                                   x-on:keydown.escape="row.open = false"
                                                   placeholder="Link a passenger (name, passport, file no.) — optional"
                                                   class="{{ $inp }} pl-8">
                                        </div>
                                        <div x-show="row.open" x-cloak class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                                            <template x-for="p in matches(row.search)" x-bind:key="p.key">
                                                <button type="button" x-on:click="pickPassenger(row, p)" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-brand-50">
                                                    <span class="truncate font-medium text-slate-800" x-text="p.name"></span>
                                                    <span class="shrink-0 font-mono text-xs text-slate-500" x-text="p.passport || p.file || ''"></span>
                                                </button>
                                            </template>
                                            <div x-show="matches(row.search).length === 0" class="px-3 py-3 text-center text-xs text-slate-400">No passengers found.</div>
                                            <button type="button" x-show="(row.search || '').trim() !== ''" x-on:click="useTyped(row)"
                                                    class="flex w-full items-center gap-2 border-t border-slate-100 px-3 py-2 text-left text-xs font-semibold text-brand-700 hover:bg-brand-50">
                                                <i class="bi bi-plus-circle"></i> Use “<span x-text="row.search.trim()"></span>” as passport no.
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-12">
                                <div class="sm:col-span-3">
                                    <label class="{{ $lbl }}">Processing Fee <span class="text-rose-500">*</span></label>
                                    <input type="text" inputmode="decimal" x-bind:name="`items[${i}][processing_fee]`" x-model="row.processing_fee" placeholder="0.00" class="{{ $inp }} text-right">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="{{ $lbl }}">MOFA Fee <span class="text-rose-500">*</span></label>
                                    <input type="text" inputmode="decimal" x-bind:name="`items[${i}][mofa_fee]`" x-model="row.mofa_fee" placeholder="0.00" class="{{ $inp }} text-right">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="{{ $lbl }}">Total (auto)</label>
                                    <div class="flex h-[38px] items-center justify-end rounded-lg bg-slate-50 px-3 text-sm font-bold text-slate-900 ring-1 ring-inset ring-slate-200" x-text="fmt(lineCents(row))"></div>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="{{ $lbl }}">Paid Amount</label>
                                    <input type="text" inputmode="decimal" x-bind:name="`items[${i}][paid_amount]`" x-model="row.paid_amount" placeholder="0.00" class="{{ $inp }} text-right">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="{{ $lbl }}">Due (auto)</label>
                                    <div class="flex h-[38px] items-center justify-end rounded-lg px-3 text-sm font-bold ring-1 ring-inset"
                                         x-bind:class="dueAmountCents(row) > 0n ? 'bg-amber-50 text-amber-800 ring-amber-200' : (dueAmountCents(row) < 0n ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-white text-slate-900 ring-slate-200')"
                                         x-text="fmt(dueAmountCents(row))"></div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="{{ $lbl }}">Remarks (optional)</label>
                                <input type="text" x-bind:name="`items[${i}][remarks]`" x-model="row.remarks" maxlength="500" placeholder="—" class="{{ $inp }}">
                            </div>
                            <p class="mt-1 text-xs font-medium text-rose-600" x-show="rowError(row)" x-text="rowError(row)"></p>
                        </div>
                    </template>
                </div>

                <button type="button" x-on:click="addRow()" x-bind:disabled="items.length >= 100"
                        class="mt-3 inline-flex w-full items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-300 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 disabled:opacity-50">
                    <i class="bi bi-plus-circle"></i> Add line item
                </button>
            </section>

            {{-- ── Step 3: Adjust & review ── --}}
            <section x-show="step === 3" x-cloak class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-calculator text-brand-600"></i> Tax &amp; discount</h2>
                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach(['tax' => 'Tax', 'discount' => 'Discount'] as $k => $title)
                            <div>
                                <label class="{{ $lbl }}">{{ $title }}</label>
                                <div class="grid grid-cols-3 gap-1 rounded-lg bg-slate-100 p-1">
                                    @foreach($adjustTypes as $type => $typeLabel)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="{{ $k }}_type" value="{{ $type }}" x-model="{{ $k }}Type" class="peer sr-only">
                                            <span class="block rounded-md px-2 py-1.5 text-center text-xs font-semibold text-slate-500 transition peer-checked:bg-white peer-checked:text-brand-700 peer-checked:shadow-sm">
                                                {{ ['none' => 'None', 'percent' => '%', 'amount' => 'Amount'][$type] }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="relative mt-2" x-show="{{ $k }}Type !== 'none'">
                                    <input type="text" inputmode="decimal" name="{{ $k }}_value" x-model="{{ $k }}Value"
                                           x-bind:disabled="{{ $k }}Type === 'none'"
                                           x-bind:placeholder="{{ $k }}Type === 'percent' ? 'e.g. 15' : '0.00'" class="{{ $inp }} pr-12 text-right">
                                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400"
                                          x-text="{{ $k }}Type === 'percent' ? '%' : symbol()"></span>
                                </div>
                                @error($k . '_value')<p class="{{ $err }}">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <label class="{{ $lbl }}">Notes / terms</label>
                        <textarea name="notes" rows="3" maxlength="2000" placeholder="Shown on the printed invoice" class="{{ $inp }}">{{ old('notes', $invoice->notes) }}</textarea>
                        @error('notes')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Review table --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-900"><i class="bi bi-eye text-brand-600"></i> Review</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2">#</th>
                                    <th class="px-4 py-2">Passenger</th>
                                    <th class="px-4 py-2 text-right">Processing</th>
                                    <th class="px-4 py-2 text-right">MOFA</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                    <th class="px-4 py-2 text-right">Paid</th>
                                    <th class="px-4 py-2 text-right">Due</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(row, i) in items" x-bind:key="'r' + row.key">
                                    <tr>
                                        <td class="px-4 py-2 text-slate-400" x-text="i + 1"></td>
                                        <td class="px-4 py-2">
                                            <div class="text-slate-500 text-xs" x-show="hasPassenger(row)" x-text="passengerLabel(row)"></div>
                                            <div x-show="!hasPassenger(row)" class="text-xs text-slate-400">—</div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right" x-text="fmt(safeCents(row.processing_fee))"></td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right" x-text="fmt(safeCents(row.mofa_fee))"></td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right font-semibold" x-text="fmt(lineCents(row))"></td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right" x-text="fmt(safeCents(row.paid_amount))"></td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right font-semibold" x-text="fmt(dueAmountCents(row))"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {{-- Wizard navigation --}}
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <button type="button" x-show="step > 1" x-on:click="go(step - 1)"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><i class="bi bi-arrow-left"></i> Back</button>
                    <a x-show="step === 1" href="{{ $invoice->exists ? route('erp.invoices.show', $invoice) : route('erp.invoices.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</a>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" x-show="step < 3" x-on:click="go(step + 1)"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Next <i class="bi bi-arrow-right"></i></button>
                    <template x-if="step === 3">
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" name="status" value="draft"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><i class="bi bi-save"></i> Save as draft</button>
                            <button type="submit" name="status" value="pending"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-brand-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-send-check"></i> {{ $invoice->exists && $invoice->status === 'pending' ? 'Save invoice' : 'Save & issue' }}</button>
                        </div>
                    </template>
                </div>
            </div>
            <p class="text-right text-xs text-slate-400" x-show="step === 3">Draft = editable &amp; deletable. Issued (pending) = can be marked paid, then it locks.</p>
        </div>

        {{-- Live totals (sticky) --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="bg-gradient-to-br from-[#1a1f2e] via-slate-800 to-brand-900 px-5 py-4 text-white">
                    <div class="text-[0.65rem] font-semibold uppercase tracking-widest text-white/60">Invoice total</div>
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
function invoiceForm() {
    const data = JSON.parse(document.getElementById('invoice-form-data').textContent);
    const init = data.initial;
    let seq = 0;
    const newRow = (r = {}) => ({
        key: ++seq,
        hr_profile_id:  r.hr_profile_id ? Number(r.hr_profile_id) : null,
        passenger_name: r.passenger_name ?? '',
        passport_no:    r.passport_no    ?? '',
        processing_fee: r.processing_fee ?? '',
        mofa_fee:       r.mofa_fee       ?? '',
        paid_amount:    r.paid_amount    ?? '',
        remarks:        r.remarks        ?? '',
        search: '', open: false,
    });
    const MONEY = /^\d+(\.\d{1,2})?$/;

    return {
        step: data.step || 1,
        passengers: data.passengers,
        agents: data.agents,
        symbols: data.symbols,
        agentId: init.agent_id ? String(init.agent_id) : '',
        billTo: { name: init.bill_to_name ?? '', phone: init.bill_to_phone ?? '', address: init.bill_to_address ?? '', email: init.bill_to_email ?? '' },
        currency: init.currency,
        taxType: init.tax_type, taxValue: init.tax_value ?? '',
        discountType: init.discount_type, discountValue: init.discount_value ?? '',
        items: (init.items && init.items.length ? init.items : [{}]).map(newRow),

        // ── integer-cent math (mirrors InvoiceService) ──
        toCents(v) {
            const s = String(v ?? '').replace(/[,\s]/g, '');
            if (s === '') return 0n;
            if (!MONEY.test(s)) return null;
            const [w, f = ''] = s.split('.');
            return BigInt(w) * 100n + BigInt((f + '00').slice(0, 2));
        },
        safeCents(v) { return this.toCents(v) ?? 0n; },
        lineCents(row) { return this.safeCents(row.processing_fee) + this.safeCents(row.mofa_fee); },
        dueAmountCents(row) { return this.lineCents(row) - this.safeCents(row.paid_amount); },
        percentOf(c, bp) { return (c * bp + 5000n) / 10000n; },
        totals() {
            const subtotal = this.items.reduce((s, r) => s + this.lineCents(r), 0n);
            const adj = (type, val) => type === 'percent' ? this.percentOf(subtotal, this.safeCents(val))
                                     : type === 'amount'  ? this.safeCents(val) : 0n;
            const tax      = adj(this.taxType, this.taxValue);
            const discount = adj(this.discountType, this.discountValue);
            return { subtotal, tax, discount, total: subtotal + tax - discount };
        },
        symbol() { return this.symbols[this.currency] || this.currency; },
        fmt(c) {
            const neg = c < 0n; if (neg) c = -c;
            const whole = (c / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            const frac  = (c % 100n).toString().padStart(2, '0');
            return (neg ? '−' : '') + this.symbol() + ' ' + whole + '.' + frac;
        },

        // ── rows ──
        addRow() { if (this.items.length < 100) this.items.push(newRow()); },
        removeRow(i) { if (this.items.length > 1) this.items.splice(i, 1); },
        rowError(row) {
            if (row.processing_fee !== '' && this.toCents(row.processing_fee) === null) return 'Processing fee must be a number with up to 2 decimals.';
            if (row.mofa_fee       !== '' && this.toCents(row.mofa_fee)       === null) return 'MOFA fee must be a number with up to 2 decimals.';
            if (row.paid_amount    !== '' && this.toCents(row.paid_amount)    === null) return 'Paid amount must be a number with up to 2 decimals.';
            return '';
        },
        matches(q) {
            q = (q || '').trim().toLowerCase();
            const list = q === '' ? this.passengers : this.passengers.filter(p =>
                (p.name || '').toLowerCase().includes(q) || (p.passport || '').toLowerCase().includes(q) || (p.file || '').toLowerCase().includes(q));
            return list.slice(0, 30);
        },
        hasPassenger(row) { return !!(row.hr_profile_id || row.passenger_name || row.passport_no); },
        passengerLabel(row) {
            if (!row.passenger_name && !row.passport_no && row.hr_profile_id) {
                const p = this.passengers.find(x => x.hr_id === Number(row.hr_profile_id));
                if (p) return p.name + (p.passport ? ' · ' + p.passport : '');
            }
            return [row.passenger_name, row.passport_no].filter(Boolean).join(' · ') || 'Passenger #' + row.hr_profile_id;
        },
        pickPassenger(row, p) {
            row.hr_profile_id = p.hr_id || null;
            row.passenger_name = p.name || '';
            row.passport_no = p.passport || '';
            row.open = false; row.search = '';
        },
        // Not in any module yet: keep the typed passport on the line.
        useTyped(row) {
            row.hr_profile_id = null;
            row.passenger_name = '';
            row.passport_no = (row.search || '').trim().toUpperCase();
            row.open = false; row.search = '';
        },
        clearPassenger(row) { row.hr_profile_id = null; row.passenger_name = ''; row.passport_no = ''; },
        pickAgent() {
            const a = this.agents.find(x => String(x.id) === this.agentId);
            if (!a) return;
            this.billTo = { name: a.name || '', phone: a.phone || '', address: a.address || '', email: this.billTo.email || '' };
        },

        // ── wizard ──
        stepError(n) {
            if (n === 1 && !this.$refs.invoiceDate.value) return 'Invoice date is required.';
            if (n === 2) {
                if (this.items.some(r => r.processing_fee === '' || r.mofa_fee === '')) return 'Enter processing fee and MOFA fee for every line (use 0 if none).';
                if (this.items.some(r => this.rowError(r))) return 'Check fees on every line — numbers with up to 2 decimals only.';
            }
            if (n === 3) {
                for (const [t, v, name] of [[this.taxType, this.taxValue, 'Tax'], [this.discountType, this.discountValue, 'Discount']]) {
                    if (t === 'none') continue;
                    const c = this.toCents(v);
                    if (c === null || String(v).trim() === '') return name + ' must be a number with up to 2 decimals.';
                    if (t === 'percent' && c > 10000n) return name + ' percentage cannot exceed 100%.';
                }
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
