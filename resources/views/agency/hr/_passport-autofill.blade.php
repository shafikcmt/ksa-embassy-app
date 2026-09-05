{{--
    Passport Auto-Fill — VISUAL placeholder card (sidebar).
    Non-functional by design: no name, no file input that posts, disabled control.
    The real AI/OCR feature is intentionally parked (service / cost / passport-image
    privacy + retention decision pending) and will replace this card later.
    Shared by create.blade.php and edit.blade.php.
--}}
<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
    <div class="mb-4 flex items-center gap-3 border-b border-slate-100 pb-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-600"><i class="bi bi-magic text-lg"></i></span>
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Passport Auto-Fill</h2>
            <p class="mt-0.5 text-xs text-slate-400">Scan to fill fields</p>
        </div>
    </div>

    <div class="flex flex-col items-center gap-2.5 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/60 px-4 py-6 text-center">
        <span class="grid h-12 w-12 place-items-center rounded-full bg-white text-slate-400 shadow-sm ring-1 ring-slate-200"><i class="bi bi-cloud-arrow-up text-2xl"></i></span>
        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-amber-700">Coming soon</span>
        <p class="text-xs leading-relaxed text-slate-400">Upload a passport image to auto-fill Name, dates, passport number and more. This feature is not available yet.</p>
        <button type="button" disabled class="mt-1 inline-flex cursor-not-allowed items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-400" title="Coming soon">
            <i class="bi bi-upload"></i> Upload passport
        </button>
    </div>
</div>
