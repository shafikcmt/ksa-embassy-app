{{--
    Invoice list row actions: View / Print / PDF + "⋯" menu (Edit, Delete).
    Expects $invoice. Delete is shown only when InvoicePolicy@delete allows it
    and opens the shared _delete_modal via the 'invoice-delete' event.
--}}
@php
    $act = 'inline-flex items-center gap-1 whitespace-nowrap rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset transition';
    $canDelete = auth()->user()->can('delete', $invoice);
    $hasMenu = $invoice->isEditable() || $canDelete;
@endphp
<div class="flex items-center justify-end gap-1">
    <a href="{{ route('erp.invoices.show', $invoice) }}" class="{{ $act }} bg-slate-50 text-slate-700 ring-slate-200 hover:bg-slate-100" title="View"><i class="bi bi-eye"></i><span class="hidden xl:inline">View</span></a>
    <a href="{{ route('erp.invoices.preview-pdf', $invoice) }}" target="_blank" class="{{ $act }} bg-brand-50 text-brand-700 ring-brand-200 hover:bg-brand-100" title="Print"><i class="bi bi-printer"></i><span class="hidden xl:inline">Print</span></a>
    <a href="{{ route('erp.invoices.download-pdf', $invoice) }}" class="{{ $act }} bg-indigo-50 text-indigo-700 ring-indigo-200 hover:bg-indigo-100" title="Download PDF"><i class="bi bi-download"></i><span class="hidden xl:inline">PDF</span></a>

    @if($hasMenu)
        <div class="relative" x-data="{ menu: false }" x-on:keydown.escape.window="menu = false">
            <button type="button" x-on:click="menu = !menu" x-bind:aria-expanded="menu" aria-haspopup="true" aria-label="More actions"
                    class="grid h-7 w-7 place-items-center rounded-md text-slate-500 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-100 hover:text-slate-800">
                <i class="bi bi-three-dots"></i>
            </button>
            <div x-show="menu" x-cloak x-transition.origin.top.right x-on:click.outside="menu = false"
                 class="absolute right-0 z-30 mt-1 w-40 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-lg">
                @if($invoice->isEditable())
                    <a href="{{ route('erp.invoices.edit', $invoice) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><i class="bi bi-pencil text-amber-600"></i> Edit</a>
                @endif
                @if($canDelete)
                    <button type="button"
                            data-action="{{ route('erp.invoices.destroy', $invoice) }}"
                            data-number="{{ $invoice->invoice_number }}"
                            data-kind="{{ $invoice->status === 'draft' ? 'draft' : ($invoice->status === 'pending' ? 'pending' : 'locked') }}"
                            x-on:click="menu = false; $dispatch('invoice-delete', { action: $el.dataset.action, number: $el.dataset.number, kind: $el.dataset.kind })"
                            class="flex w-full items-center gap-2 px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50"><i class="bi bi-trash"></i> Delete</button>
                @endif
            </div>
        </div>
    @endif
</div>
