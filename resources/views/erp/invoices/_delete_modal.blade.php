{{--
    Shared invoice delete modal (index + show). Open it from any element inside
    an Alpine scope with:
        x-on:click="$dispatch('invoice-delete', { action: $el.dataset.action, number: $el.dataset.number, kind: $el.dataset.kind })"
    where data-kind is draft | pending | locked. Server re-validates the reason
    (min 5) and the typed number (exact, trimmed) — the JS gate is only UX.
    After a failed submit the modal re-opens with the previous input.
--}}
@php
    // Only re-open for an action URL inside this module (old input is user-supplied).
    $reopen = ($errors->has('delete_reason') || $errors->has('confirm_number'))
        && str_starts_with((string) old('_delete_action'), url('/erp/invoices/'));
    $mInp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500';
@endphp
<div x-data="{
        open: {{ $reopen ? 'true' : 'false' }},
        action: @js($reopen ? (string) old('_delete_action') : ''),
        number: @js($reopen ? (string) old('_delete_number') : ''),
        kind: @js($reopen ? (string) old('_delete_kind') : 'draft'),
        reason: @js($reopen ? (string) old('delete_reason', '') : ''),
        typed: @js($reopen ? (string) old('confirm_number', '') : ''),
        get ready() { return this.reason.trim().length >= 5 && this.typed.trim() === this.number; },
        show(d) { this.action = d.action; this.number = d.number; this.kind = d.kind || 'draft'; this.reason = ''; this.typed = ''; this.open = true; this.$nextTick(() => this.$refs.reason.focus()); },
     }"
     x-on:invoice-delete.window="show($event.detail)"
     x-on:keydown.escape.window="open = false">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="open = false"></div>
        <form method="POST" x-bind:action="action" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            @csrf @method('DELETE')
            <input type="hidden" name="_delete_action" x-bind:value="action">
            <input type="hidden" name="_delete_number" x-bind:value="number">
            <input type="hidden" name="_delete_kind" x-bind:value="kind">

            <h3 class="flex items-center gap-2 text-base font-bold text-slate-900">
                <i class="bi bi-trash text-rose-600"></i> Delete invoice <span class="font-mono" x-text="number"></span>
            </h3>

            <div class="mt-3 flex gap-2 rounded-xl border px-3 py-2.5 text-sm"
                 x-bind:class="kind === 'draft' ? 'border-slate-200 bg-slate-50 text-slate-700' : 'border-rose-200 bg-rose-50 text-rose-800'">
                <i class="bi" x-bind:class="kind === 'draft' ? 'bi-info-circle' : 'bi-exclamation-triangle-fill'"></i>
                <span x-show="kind === 'draft'">This draft invoice will be removed.</span>
                <span x-show="kind === 'pending'">This invoice has been issued (pending). It will be removed from the invoice list and summary totals.</span>
                <span x-show="kind === 'locked'">This invoice is paid/locked. It will be removed from the invoice list and summary totals.</span>
            </div>

            <div class="mt-4 space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Reason for deleting <span class="text-rose-500">*</span></label>
                    <textarea name="delete_reason" x-ref="reason" x-model="reason" rows="2" maxlength="1000" required minlength="5"
                              placeholder="At least 5 characters" class="{{ $mInp }}"></textarea>
                    @error('delete_reason')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">
                        Type <span class="font-mono font-bold text-slate-800" x-text="number"></span> to confirm <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="confirm_number" x-model="typed" autocomplete="off" spellcheck="false" required class="{{ $mInp }} font-mono">
                    @error('confirm_number')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" x-on:click="open = false" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" x-bind:disabled="!ready"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-40">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>
