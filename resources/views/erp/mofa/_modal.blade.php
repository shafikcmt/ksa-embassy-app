@php
// [key => [label, type, required, span]] per section. Field keys/ids/names are unchanged; mofaModal() focuses by [name].
// Compact layout: 2 cards on a 4/2/1-column grid + a small Remarks card. `span` widens a field on
// desktop so no row is left half-empty. MOFA Issue Date is no longer asked for; passport_validity
// is a UI-only helper (not in `f`, never sent to the server); 'reference' is the agent select.
$sections = [
 ['bi-person-vcard', 'Personal & Passport Information', ['full_name'=>['Full Name','text',true], 'father_name'=>["Father’s Name",'text',true], 'mother_name'=>["Mother’s Name",'text',true], 'passport_number'=>['Passport Number','text',true], 'date_of_birth'=>['Date of Birth','date',true], 'age'=>['Age','number',false], 'issue_date'=>['Passport Issue Date','date',true], 'passport_validity'=>['Passport Validity','validity',false], 'expiry_date'=>['Passport Expiry Date','date',true], 'reference'=>['Reference','reference',false,'lg:col-span-3']]],
 ['bi-calendar2-check', 'MOFA & Visa Details', ['mofa_number'=>['MOFA Number','text',false], 'mofa_date'=>['MOFA Date','date',false], 'mofa_expiry_date'=>['MOFA Expiry Date','date',true], 'left_day'=>['Left Day','number',false], 'visa_number'=>['Visa Number','text',false,'lg:col-span-2'], 'id_number'=>['ID Number','text',false,'lg:col-span-2']]],
];
$config=['base'=>url('erp/mofa'),'search'=>route('erp.mofa.hr-search'),'today'=>today()->format('Y-m-d'),'todayDhaka'=>now(\App\Models\MofaEntry::LEFT_DAY_TIMEZONE)->format('Y-m-d'),'mofaDays'=>\App\Models\MofaEntry::MOFA_VALIDITY_DAYS,'validity'=>\App\Models\MofaEntry::DEFAULT_PASSPORT_VALIDITY,'csrf'=>csrf_token(),'add'=>request()->boolean('add'),'edit'=>(int)request('edit'),'fields'=>array_merge(\App\Http\Controllers\Erp\MofaController::FIELDS,['mofa_expiry_date'])];
$inp = \App\Support\ErpForm::INPUT;
$state = fn (string $k) => 'x-bind:class="error(\'' . $k . '\') ? \'' . \App\Support\ErpForm::BORDER_ERROR . '\' : \'' . \App\Support\ErpForm::BORDER_OK . '\'"'
    . ' x-bind:aria-invalid="!!error(\'' . $k . '\')"';
@endphp
<script type="application/json" id="mofa-config">{!! json_encode($config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
<div x-data="mofaModal()" @mofa-add.window="open()" @mofa-edit.window="open($event.detail.id)" x-on:erp-autofilled="afterAutofill($event.detail.keys)">
    <x-erp.modal kind="dialog" icon="bi-file-earmark-text" title-id="mf-dialog-title"
                 title="id ? 'Edit MOFA Entry' : 'Add MOFA Entry'"
                 submit="save()" close="close()" busy="saving" disabled="saving || failedLoad" edit="!!id" loading="loading" size="wide" dense>
        <p x-show="message" x-text="message" role="alert" class="rounded-lg bg-red-50 p-3 text-sm font-medium text-red-700"></p>

        @foreach($sections as [$icon, $title, $fields])
        <x-erp.section :icon="$icon" :title="$title" cols="4" dense>
            @foreach($fields as $key=>$def)
            @php [$label,$type,$required,$span] = array_pad($def, 4, null); @endphp
            <x-erp.field :label="$label" :for="'mf-'.$key" :required="$required" :auto="in_array($key,['age','left_day'])"
                         :error="in_array($key,['age','left_day']) ? null : 'error(\''.$key.'\')'" :error-id="'mf-error-'.$key" :class="$span">
                @if($key==='reference')
                    <x-erp.select-search id="mf-reference" :agents="$agentOptions ?? []" x-model="f.reference" x-on:change="changed('reference')" error="error('reference')" />
                @elseif($key==='age')
                    <input id="mf-age" readonly tabindex="-1" x-bind:value="age()" class="{{ \App\Support\ErpForm::READONLY }}">
                @elseif($key==='passport_validity')
                    {{-- UI helper: Expiry = Issue Date + N years − 1 day (recalculated only on user change). --}}
                    <select id="mf-passport_validity" x-model.number="validity" @change="recalcPassport()" class="{{ $inp }} {{ \App\Support\ErpForm::BORDER_OK }}">
                        @foreach(\App\Models\MofaEntry::PASSPORT_VALIDITY_YEARS as $years)<option value="{{ $years }}">{{ $years }} Years</option>@endforeach
                    </select>
                @elseif($key==='left_day')
                    {{-- Red input when under 30 days (no helper text). --}}
                    <input id="mf-left_day" readonly tabindex="-1" x-bind:value="leftDay()"
                           x-bind:class="leftDay() !== '' && leftDay() < 30 ? '{{ \App\Support\ErpForm::READONLY_ALERT }}' : '{{ \App\Support\ErpForm::READONLY }}'">
                @else
                    <input id="mf-{{ $key }}" name="{{ $key }}" type="{{ $type }}" class="{{ $inp }}" x-model="f.{{ $key }}" @input="changed('{{ $key }}'); recalcFrom('{{ $key }}')" @blur="touched.{{ $key }}=true" {!! $state($key) !!} aria-describedby="mf-error-{{ $key }}{{ $key==='passport_number' ? ' mf-passport_number-erp' : '' }}" @required($required) @if($type==='text') maxlength="100" @endif @if($key==='date_of_birth') max="{{ today()->subDay()->format('Y-m-d') }}" @endif @if($key==='passport_number') autocomplete="off" @endif>
                @endif
                @if($key==='passport_number')
                    {{-- Cross-module ERP auto-fill. MOFA's issue/expiry are PASSPORT dates, so they
                         map only from passport_issue/expiry (earlier MOFA or HR), never from
                         Stamping's visa dates. --}}
                    @include('erp.partials._erp-autofill', [
                        'module'      => 'mofa',
                        'passportKey' => 'passport_number',
                        'inputId'     => 'mf-passport_number',
                        'idKey'       => 'id',
                        'map'         => [
                            'full_name' => 'full_name', 'father_name' => 'father_name', 'mother_name' => 'mother_name',
                            'date_of_birth' => 'date_of_birth', 'mofa_number' => 'mofa_number', 'mofa_date' => 'mofa_date',
                            'visa_number' => 'visa_number', 'id_number' => 'id_number',
                            'passport_issue_date' => 'issue_date', 'passport_expiry_date' => 'expiry_date',
                        ],
                    ])
                @endif
            </x-erp.field>
            @endforeach
        </x-erp.section>
        @endforeach

        {{-- Remarks: small full-width card (same look as a dense section); the textarea's own
             label is its heading, so there is no duplicate title. --}}
        <div class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
            <x-erp.textarea label="Remarks" id="mf-remarks" rows="2" maxlength="2000" x-model="f.remarks" x-on:input="changed('remarks')"
                            error="error('remarks')" error-id="mf-error-remarks" />
        </div>
    </x-erp.modal>
</div>
@push('scripts')
<script>
function mofaModal() {
    const cfg=JSON.parse(document.getElementById('mofa-config').textContent);
    const required=['full_name','father_name','mother_name','passport_number','date_of_birth','issue_date','expiry_date','mofa_expiry_date'];
    // Local-calendar date helpers. Never toISOString(): UTC would shift Dhaka dates back a day.
    const ymd=d=>d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
    const parts=s=>{const m=/^(\d{4})-(\d{2})-(\d{2})$/.exec(s||'');return m?[+m[1],+m[2],+m[3]]:null;};
    // Same day N years later, minus 1 day. 29 Feb in a non-leap target year rolls to 1 Mar, giving 28 Feb.
    const passportExpiry=(issue,years)=>{const p=parts(issue);if(!p)return '';const d=new Date(p[0]+years,p[1]-1,p[2]);d.setDate(d.getDate()-1);return ymd(d);};
    const mofaExpiry=date=>{const p=parts(date);return p?ymd(new Date(p[0],p[1]-1,p[2]+cfg.mofaDays)):'';};
    const dayNo=s=>{const p=parts(s);return p?Date.UTC(p[0],p[1]-1,p[2])/86400000:null;};
    return {
        f:{},validity:cfg.validity,id:null,touched:{},server:{},message:'',loading:false,saving:false,failedLoad:false,suggestions:[],looking:false,lookupError:'',timer:null,lookupVersion:0,openVersion:0,returnFocus:null,
        init(){if(cfg.add || cfg.edit) this.$nextTick(()=>this.open(cfg.edit || null));},
        async open(id=null){
            this.returnFocus=document.activeElement;this.id=id;this.f=Object.fromEntries(cfg.fields.map(k=>[k,'']));this.validity=cfg.validity;this.touched={};this.server={};this.message='';this.suggestions=[];this.failedLoad=false;this.lookupVersion++;const version=++this.openVersion;
            this.$refs.dialog.showModal();document.body.style.overflow='hidden';
            if(id){this.loading=true;try{const r=await fetch(cfg.base+'/'+id,{headers:{Accept:'application/json'}});if(!r.ok)throw Error('Unable to load this entry. Close and try again.');const data=await r.json();if(version===this.openVersion)this.f=Object.assign(this.f,data);}catch(e){if(version===this.openVersion){this.message=e.message;this.failedLoad=true;}}finally{if(version===this.openVersion)this.loading=false;}}
            this.$nextTick(()=>this.$refs.dialog.querySelector('[name="full_name"]')?.focus());
        },
        close(){if(this.saving)return;this.openVersion++;this.lookupVersion++;clearTimeout(this.timer);this.loading=false;this.looking=false;this.$refs.dialog.close();document.body.style.overflow='';this.returnFocus?.focus();},
        changed(k){this.touched[k]=true;delete this.server[k];},
        // Auto-dates run only on user edits (never on edit-load), so saved expiries are kept; users may still override them.
        recalcFrom(k){if(k==='issue_date')this.recalcPassport();if(k==='mofa_date')this.recalcMofa();},
        recalcPassport(){const e=passportExpiry(this.f.issue_date,Number(this.validity));if(e){this.f.expiry_date=e;this.changed('expiry_date');}},
        recalcMofa(){const e=mofaExpiry(this.f.mofa_date);if(e){this.f.mofa_expiry_date=e;this.changed('mofa_expiry_date');}},
        // Auto-fill only fills empty fields; derive an expiry it left empty.
        afterAutofill(keys){keys=keys||[];if(keys.includes('issue_date')&&!this.f.expiry_date)this.recalcPassport();if(keys.includes('mofa_date')&&!this.f.mofa_expiry_date)this.recalcMofa();},
        errors(){const e={};for(const k of required)if(!String(this.f[k]??'').trim())e[k]='This field is required.';
            if(this.f.date_of_birth && this.f.date_of_birth>=cfg.today)e.date_of_birth='Date of birth must be before today.';
            if(this.f.issue_date&&this.f.expiry_date&&this.f.expiry_date<=this.f.issue_date)e.expiry_date='Expiry date must be after issue date.';
            for(const k of ['full_name','father_name','mother_name','passport_number','visa_number','id_number','mofa_number'])if(String(this.f[k]??'').length>100)e[k]='Use no more than 100 characters.';
            return e;},
        error(k){return this.server[k]?.[0] || (this.touched[k]?this.errors()[k]:'') || '';},
        age(){return this.f.date_of_birth ? Number(cfg.today.slice(0,4))-Number(this.f.date_of_birth.slice(0,4)) : '';},
        // MOFA Expiry − today (Dhaka calendar day); negative once expired, like the list/print.
        leftDay(){const e=dayNo(this.f.mofa_expiry_date);return e===null?'':e-dayNo(cfg.todayDhaka);},
        // Passport auto-fill now comes from erp.partials._erp-autofill (all ERP modules, HR fallback).
        async save(){if(this.saving||this.failedLoad)return;required.forEach(k=>this.touched[k]=true);if(Object.keys(this.errors()).length){this.$refs.dialog.querySelector('[name="'+Object.keys(this.errors())[0]+'"]')?.focus();return;}this.saving=true;this.message='';this.server={};
            try{const r=await fetch(cfg.base+(this.id?'/'+this.id:''),{method:this.id?'PUT':'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':cfg.csrf},body:JSON.stringify(this.f)});const data=await r.json();if(r.status===422){this.server=data.errors||{};this.message='Please fix the highlighted fields.';this.$nextTick(()=>this.$refs.dialog.querySelector('[aria-invalid="true"]')?.focus());return;}if(!r.ok)throw Error(data.message||'Unable to save. Please try again.');const url=new URL(window.location.href);url.searchParams.delete('add');url.searchParams.delete('edit');window.location.assign(url.href);}catch(e){this.message=e.message||'Unable to save. Please try again.';}finally{this.saving=false;}}
    };
}
</script>
@endpush
