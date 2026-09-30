@extends('layouts.agency-app')
@section('title', 'Employment Contract')
@section('page-title', 'Employment Contract')

{{--
    Employment Contract (bilingual EN / AR, one A4 page).
    Top card = editable details, prefilled from the HR record ("HR Pool"); the
    contract below updates live. Nothing here is saved — it is a print page.
    "Print / Save as PDF" uses the browser print dialog; @media print shows only
    the A4 sheet.
--}}

@section('content')
<div x-data="hrContract(@js($contract))" class="mx-auto max-w-5xl">

    {{-- Top bar --}}
    <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
        <a href="{{ route('hr.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-brand-600"><i class="bi bi-arrow-left"></i> Back to All HR</a>
        <h1 class="text-sm font-bold text-slate-800">Employment Contract</h1>
        <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
            <i class="bi bi-printer"></i> Print / Save as PDF
        </button>
    </div>

    {{-- Contract details (editable) --}}
    <div class="no-print mx-auto mb-5 max-w-3xl rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
            Contract details
            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600"><i class="bi bi-check-circle"></i> Filled from HR Pool</span>
        </div>
        @php $inp = 'h-9 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-400 focus:ring-brand-400'; $lbl = 'mb-1 block text-xs font-semibold text-slate-600'; $sub = 'font-normal text-slate-400'; @endphp
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="{{ $lbl }}">Passport No. <span class="{{ $sub }}">(type this — the rest fills in)</span></label>
                <input type="text" x-model="f.passport" @change="lookup()" @keydown.enter.prevent="lookup()" class="{{ $inp }} font-mono uppercase">
                <p x-show="msg" x-text="msg" class="mt-1 text-xs text-rose-600" x-cloak></p>
            </div>
            <div class="sm:col-span-2">
                <label class="{{ $lbl }}">1st party <span class="{{ $sub }}">(company — from HR Pool Sponsor Name)</span></label>
                <div class="flex gap-2">
                    <input type="text" x-model="f.firstParty" dir="auto" class="{{ $inp }}">
                    <button type="button" @click="toggleEnglish()" class="inline-flex h-9 shrink-0 items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                        <i class="bi bi-translate"></i> <span x-text="english ? '→ Arabic' : '→ English'"></span>
                    </button>
                </div>
            </div>
            <div>
                <label class="{{ $lbl }}">2nd party <span class="{{ $sub }}">(passenger name)</span></label>
                <input type="text" x-model="f.secondParty" class="{{ $inp }} uppercase">
            </div>
            <div>
                <label class="{{ $lbl }}">Nationality</label>
                <input type="text" x-model="f.nationality" class="{{ $inp }} uppercase">
            </div>
            <div class="sm:col-span-2">
                <label class="{{ $lbl }}">Profession <span class="{{ $sub }}">(from HR Pool)</span></label>
                <input type="text" x-model="f.profession" class="{{ $inp }}">
            </div>
            <div class="grid grid-cols-3 gap-3 sm:col-span-2">
                <div><label class="{{ $lbl }}">Monthly salary (SR)</label><input type="text" x-model="f.salary" inputmode="numeric" class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Contract (years)</label><input type="text" x-model="f.years" inputmode="numeric" class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Departure city</label><input type="text" x-model="f.city" class="{{ $inp }}"></div>
            </div>
        </div>
    </div>

    {{-- ── A4 contract sheet ─────────────────────────────────────────── --}}
    <div class="contract-sheet">
        <div class="cs-frame">
            <table class="cs-head">
                <tr>
                    <td class="cs-title" colspan="2">EMPLOYMENT CONTRACT</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="cs-lbl">1<sup>st</sup> party:</td>
                    <td class="cs-val" dir="auto" x-text="f.firstParty"></td>
                    <td class="cs-ar">الطرف الأول</td>
                </tr>
                <tr>
                    <td class="cs-lbl">2<sup>nd</sup> party:</td>
                    <td class="cs-val" x-text="f.secondParty"></td>
                    <td class="cs-ar">الطرف الثاني</td>
                </tr>
                <tr>
                    <td class="cs-lbl">Nationality:</td>
                    <td class="cs-val" x-text="f.nationality"></td>
                    <td class="cs-ar">الجنسية</td>
                </tr>
                <tr>
                    <td class="cs-lbl">Passport No.</td>
                    <td class="cs-val" x-text="f.passport"></td>
                    <td class="cs-ar">جواز سفر رقم</td>
                </tr>
                <tr>
                    <td class="cs-lbl">Profession:</td>
                    <td class="cs-val" x-text="f.profession"></td>
                    <td class="cs-ar">المهنة</td>
                </tr>
            </table>

            <table class="cs-terms">
                <template x-for="(t, i) in terms()" :key="i">
                    <tr>
                        <td class="cs-n" x-text="(i + 1) + '.'"></td>
                        <td class="cs-en" x-html="t.en"></td>
                        <td class="cs-ar-txt" x-text="t.ar"></td>
                        <td class="cs-n-ar" x-text="arNum(i + 1) + '.'"></td>
                    </tr>
                </template>
            </table>

            <table class="cs-sign">
                <tr>
                    <td>Signature of the Employer</td>
                    <td style="text-align:right">Signature of the Employee</td>
                </tr>
            </table>
            {{-- Space for the company stamp / emblem --}}
            <div class="cs-stamp"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@400;600&display=swap" rel="stylesheet">
<style>
    .contract-sheet { width: 210mm; min-height: 297mm; margin: 0 auto 2rem; background: #fff; padding: 12mm 14mm;
        box-shadow: 0 1px 12px rgba(15, 23, 42, .12); color: #000; box-sizing: border-box; }
    .cs-frame { border: 1px solid #000; min-height: 271mm; padding: 5mm 7mm; box-sizing: border-box; display: flex; flex-direction: column; }
    .contract-sheet table { width: 100%; border-collapse: collapse; }
    .contract-sheet td { vertical-align: top; }
    .cs-head, .cs-terms, .cs-sign { font-family: "Times New Roman", Times, serif; }
    .cs-title { font-weight: bold; font-size: 13pt; padding-bottom: 2mm; }
    .cs-lbl { width: 24mm; font-size: 10.5pt; padding: .6mm 0; white-space: nowrap; }
    .cs-val { font-size: 13pt; padding: .3mm 2mm; text-align: left; unicode-bidi: plaintext; }
    .cs-ar, .cs-ar-txt { font-family: "Traditional Arabic", "Noto Naskh Arabic", "Times New Roman", serif; direction: rtl; text-align: right; }
    .cs-ar { width: 32mm; font-size: 11.5pt; }
    .cs-terms { margin-top: 6mm; }
    .cs-terms td { padding-bottom: 4mm; font-size: 11pt; line-height: 1.3; }
    .cs-n { width: 7mm; }
    .cs-en { width: 48%; padding-right: 6mm; text-align: left; }
    .cs-ar-txt { font-size: 12pt !important; line-height: 1.45 !important; }
    .cs-n-ar { width: 7mm; text-align: right; font-family: "Traditional Arabic", "Noto Naskh Arabic", serif; font-size: 12pt !important; }
    .cs-sign { margin-top: auto; padding-top: 8mm; font-size: 12pt; }
    .cs-stamp { height: 22mm; }

    @media screen and (max-width: 860px) {
        .contract-sheet { width: 100%; min-height: 0; padding: 4mm; }
        .cs-frame { min-height: 0; }
    }
    @page { size: A4; margin: 0; }
    @media print {
        body * { visibility: hidden !important; }
        .contract-sheet, .contract-sheet * { visibility: visible !important; }
        .contract-sheet { position: absolute; left: 0; top: 0; margin: 0; box-shadow: none; width: 210mm; min-height: 0; height: 296mm; }
        .no-print { display: none !important; }
    }
</style>
@endpush

@push('scripts')
@include('agency.hr._arabic-to-english')
<script>
    function hrContract(initial) {
        const AR = '٠١٢٣٤٥٦٧٨٩';
        const arDigits = s => String(s ?? '').replace(/[0-9]/g, d => AR[d]);
        const WORDS = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'];
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const CITY_AR = { dhaka: 'دكا', chittagong: 'شيتاغونغ', chattogram: 'شيتاغونغ', sylhet: 'سلهت', kathmandu: 'كاتماندو', karachi: 'كراتشي', lahore: 'لاهور', mumbai: 'مومباي', delhi: 'دلهي', manila: 'مانيلا', colombo: 'كولومبو' };
        return {
            f: { ...initial },
            arabic: initial.firstParty,
            english: false,
            msg: '',
            arNum(n) { return arDigits(n); },
            toggleEnglish() {
                if (!this.english) {
                    this.arabic = this.f.firstParty;
                    const en = this.f.firstParty && window.HrArEn ? window.HrArEn.name(this.f.firstParty) : '';
                    this.f.firstParty = initial.firstPartyEn && initial.firstParty === this.arabic ? initial.firstPartyEn : (en || this.f.firstParty);
                } else {
                    this.f.firstParty = this.arabic;
                }
                this.english = !this.english;
            },
            async lookup() {
                const p = (this.f.passport || '').trim();
                this.msg = '';
                if (!p || p.toUpperCase() === (initial.passport || '').toUpperCase()) return;
                try {
                    const r = await fetch(@js(route('hr.lookup-by-passport')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                        body: JSON.stringify({ passport_no: p }),
                    });
                    const d = await r.json();
                    if (d && d.found && d.id) { window.location = @js(url('hr')) + '/' + d.id + '/contract'; return; }
                    this.msg = (d && d.message) || 'No candidate found with that passport number.';
                } catch (e) { this.msg = 'Lookup failed. You can still edit the fields manually.'; }
            },
            terms() {
                const sal = esc(this.f.salary || '1000'), yrs = parseInt(this.f.years, 10) || 2;
                const city = esc(this.f.city || 'Dhaka');
                const cityAr = CITY_AR[(this.f.city || 'Dhaka').trim().toLowerCase()] || (this.f.city || 'دكا');
                const yrsWord = WORDS[yrs] || String(yrs);
                const yrsAr = yrs === 1 ? 'سنة واحدة' : (yrs === 2 ? 'سنتان' : arDigits(yrs) + ' سنوات');
                return [
                    { en: `That the 1<sup>st</sup> party shall pay to the 2<sup>nd</sup> party a monthly salary of ${sal}Sr. plus overtime accordingly to Saudi Labour Law.`,
                      ar: `إن الطرف الأول يدفع للطرف الثاني راتباً شهرياً ${arDigits(this.f.salary || '1000')} ريال سعودي بالإضافة إلى العمل الإضافي حسب قانون العمل بالمملكة العربية السعودية` },
                    { en: 'That 1<sup>st</sup> party should provide 2<sup>nd</sup> parties free medical, free single accommodation and free food facilities during the period of contract in the Kingdom of Saudi Arabia',
                      ar: 'يلتزم الطرف الأول بتوفير العلاج والسكن والطعام للطرف الثاني مجاناً خلال مدة العقد في المملكة العربية السعودية' },
                    { en: 'That the 1<sup>st</sup> party shall provide free transportation from resident to the work site',
                      ar: 'يلتزم الطرف الأول بنقل الطرف الثاني من السكن إلى محل العمل مجاناً' },
                    { en: `The period of contract is of ${yrs} (${yrsWord}) years`,
                      ar: `إن مدة العقد ${yrsAr}` },
                    { en: `That the 1<sup>st</sup> party shall bear the passage cost from ${city} to K.S.A and back to ${city} for joining the service and the return ticket would provided after completion this agreement.`,
                      ar: `يتحمل الطرف الأول قيمة تذكرة السفر من ${cityAr} إلى المملكة العربية السعودية لمباشرة العمل وتذكرة العودة إلى ${cityAr} بعد انتهاء مدة العقد` },
                    { en: 'Daily working hours shall be 8 hours.',
                      ar: 'ساعات العمل تكون (٨) ساعات يومياً' },
                    { en: 'That this agreement shall come in effect from the date of arrival of the 2<sup>nd</sup> party in the Kingdom of Saudi Arabia.',
                      ar: 'يعتبر هذا العقد سارياً بعد وصول الطرف الثاني إلى المملكة العربية السعودية' },
                    { en: 'That the 2<sup>nd</sup> party shall undertake to abide by the instruction and rules enforced in the Kingdom of Saudi Arabia.',
                      ar: 'يلتزم الطرف الثاني بجميع التعليمات والقرارات السارية المفعول في المملكة العربية السعودية' },
                    { en: 'That any other terms and conditions not mentioned in the demand letter shall be following as per Saudi Labour Laws.',
                      ar: 'أي شرط لم يذكر في ورقة الطلب يعمل به حسب قانون العمل بالمملكة العربية السعودية' },
                ];
            },
        };
    }
</script>
@endpush
