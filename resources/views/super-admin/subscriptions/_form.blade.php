<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Agency <span class="text-danger">*</span></label>
        <select name="agency_id" class="form-select @error('agency_id') is-invalid @enderror" required>
            <option value="">— Select Agency —</option>
            @foreach($agencies as $ag)
                <option value="{{ $ag->id }}"
                    @selected(old('agency_id', request('agency_id')) == $ag->id)>
                    {{ $ag->name }}
                </option>
            @endforeach
        </select>
        @error('agency_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Plan <span class="text-danger">*</span></label>
        <select name="plan_id" class="form-select @error('plan_id') is-invalid @enderror" required>
            <option value="">— Select Plan —</option>
            @foreach($plans as $plan)
                <option value="{{ $plan->id }}"
                    @selected(old('plan_id', $subscription->plan_id ?? '') == $plan->id)>
                    {{ $plan->name }} — {{ $plan->priceLabel('') }}
                </option>
            @endforeach
        </select>
        @error('plan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
            value="{{ old('start_date', ($subscription->start_date ?? now())->format('Y-m-d')) }}" required>
        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
            value="{{ old('end_date', ($subscription->end_date ?? now()->addDays(30))->format('Y-m-d')) }}" required>
        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Amount (USD)</label>
        <input type="number" name="amount" class="form-control" step="0.01" min="0"
            value="{{ old('amount', $subscription->amount ?? 0) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Subscription Status</label>
        <select name="status" class="form-select">
            @foreach(['trial','active','expired','suspended'] as $s)
                <option value="{{ $s }}"
                    @selected(old('status', $subscription->status ?? 'trial') === $s)>
                    {{ ucfirst($s) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Payment Status</label>
        <select name="payment_status" class="form-select">
            @foreach(['pending','paid','failed','waived'] as $s)
                <option value="{{ $s }}"
                    @selected(old('payment_status', $subscription->payment_status ?? 'pending') === $s)>
                    {{ ucfirst($s) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $subscription->notes ?? '') }}</textarea>
    </div>
</div>
