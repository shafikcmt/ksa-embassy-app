@php
$config = ['base'=>url('erp/bmet'), 'search'=>route('erp.bmet.hr-search'), 'today'=>today()->toDateString(), 'csrf'=>csrf_token(), 'add'=>request()->boolean('add'), 'edit'=>(int)request('edit'), 'agents'=>$agents->values(), 'fields'=>array_merge(\App\Http\Controllers\Erp\BmetController::FIELDS,['agent_id','status'])];
$inp = \App\Support\ErpForm::INPUT;
// Border/aria state for a field key; bmetModal() focuses the first error by [name] / [aria-invalid].
$state = fn (string $k) => 'x-bind:class="error(\'' . $k . '\') ? \'' . \App\Support\ErpForm::BORDER_ERROR . '\' : \'' . \App\Support\ErpForm::BORDER_OK . '\'"'
    . ' x-bind:aria-invalid="!!error(\'' . $k . '\')" aria-describedby="bm-error-' . $k . '"';
// Visa/ID: read-only (gray) until "Enter or edit manually" is ticked.
$visaState = fn (string $k) => 'x-bind:readonly="!manualVisa"'
    . ' x-bind:class="[manualVisa ? \'bg-white text-slate-800\' : \'cursor-not-allowed bg-slate-100 text-slate-500\', error(\'' . $k . '\') ? \'' . \App\Support\ErpForm::BORDER_ERROR . '\' : \'' . \App\Support\ErpForm::BORDER_OK . '\']"'
    . ' x-bind:aria-invalid="!!error(\'' . $k . '\')" aria-describedby="bm-error-' . $k . '"';
$ro = 'block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20';
@endphp
<script type="application/json" id="bmet-config">{!! json_encode($config,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
<div x-data="bmetModal()" @bmet-add.window="open()" @bmet-edit.window="open($event.detail.id)"
     @erp-autofilled="if ($event.detail.keys.some(k => ['visa_number', 'id_number'].includes(k))) manualVisa = false">
    <x-erp.modal kind="dialog" icon="bi-person-check" title-id="bm-dialog-title"
                 title="id ? 'Edit BMET Clearance Entry' : 'Add BMET Clearance Entry'"
                 submit="save()" close="close()" busy="saving" disabled="saving || failedLoad" edit="!!id" loading="loading"
                 class="transition duration-150" x-bind:class="closing ? 'opacity-0 scale-95' : ''">
        <p x-show="message" x-text="message" role="alert" class="rounded-lg bg-red-50 p-3 text-sm font-medium text-red-700"></p>

        {{-- Section 1 — Candidate --}}
        <x-erp.section icon="bi-person-vcard" title="Candidate Information">
            <x-erp.field label="Passenger Name" for="bm-full_name" required error="error('full_name')" error-id="bm-error-full_name">
                <input id="bm-full_name" name="full_name" type="text" maxlength="100" class="{{ $inp }}" x-model="f.full_name" @input="changed('full_name')" @blur="touched.full_name=true" required {!! $state('full_name') !!}>
            </x-erp.field>
            <x-erp.field label="Passport Number" for="bm-passport_number" required error="error('passport_number')" error-id="bm-error-passport_number">
                <div class="relative">
                    <input id="bm-passport_number" name="passport_number" type="text" maxlength="100" autocomplete="off" spellcheck="false" class="{{ $inp }} pr-9" x-model="f.passport_number" @input="changed('passport_number')" @blur="touched.passport_number=true" required
                           x-bind:class="error('passport_number') ? '{{ \App\Support\ErpForm::BORDER_ERROR }}' : '{{ \App\Support\ErpForm::BORDER_OK }}'" x-bind:aria-invalid="!!error('passport_number')" aria-describedby="bm-error-passport_number bm-passport_number-erp">
                    <i class="bi bi-search pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true"></i>
                </div>
                {{-- Cross-module ERP auto-fill (Medical → MOFA → Stamping → BMET, HR fallback). --}}
                @include('erp.partials._erp-autofill', [
                    'module'      => 'bmet',
                    'passportKey' => 'passport_number',
                    'inputId'     => 'bm-passport_number',
                    'idKey'       => 'id',
                    'map'         => [
                        'full_name' => 'full_name', 'father_name' => 'father_name',
                        'visa_number' => 'visa_number', 'id_number' => 'id_number', 'reference' => 'reference',
                    ],
                ])
            </x-erp.field>
            <x-erp.field label="Father’s Name" for="bm-father_name" error="error('father_name')" error-id="bm-error-father_name">
                <input id="bm-father_name" name="father_name" type="text" maxlength="100" class="{{ $inp }}" x-model="f.father_name" @input="changed('father_name')" @blur="touched.father_name=true" {!! $state('father_name') !!}>
            </x-erp.field>
        </x-erp.section>

        {{-- Section 2 — BMET details --}}
        <x-erp.section icon="bi-patch-check" title="BMET Details">
            <x-erp.field label="EC Number" for="bm-ec_number" error="error('ec_number')" error-id="bm-error-ec_number">
                <input id="bm-ec_number" name="ec_number" type="text" maxlength="100" class="{{ $inp }}" x-model="f.ec_number" @input="changed('ec_number')" @blur="touched.ec_number=true" {!! $state('ec_number') !!}>
            </x-erp.field>
            <x-erp.field label="Agent" for="bm-agent_id" error="error('agent_id')" error-id="bm-error-agent_id">
                <select id="bm-agent_id" name="agent_id" class="{{ $inp }}" x-model="f.agent_id" @change="agentChanged(); changed('agent_id')" {!! $state('agent_id') !!}>
                    <option value="">No agent selected</option>
                    @foreach($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}{{ $agent->status==='active'?'':' (inactive)' }}</option>@endforeach
                </select>
            </x-erp.field>
        </x-erp.section>

        {{-- Section 3 — Visa details (read-only after auto-fill unless edited manually) --}}
        <x-erp.section icon="bi-postage" title="Visa Details">
            <x-slot:actions>
                <label class="flex items-center gap-2 text-xs font-medium text-slate-600"><input type="checkbox" x-model="manualVisa" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Enter or edit manually</label>
            </x-slot:actions>
            <x-erp.field label="Visa Number" for="bm-visa_number" error="error('visa_number')" error-id="bm-error-visa_number">
                <input id="bm-visa_number" name="visa_number" type="text" maxlength="100" class="{{ $ro }}" x-model="f.visa_number" @input="changed('visa_number')" @blur="touched.visa_number=true" {!! $visaState('visa_number') !!}>
            </x-erp.field>
            <x-erp.field label="ID Number" for="bm-id_number" error="error('id_number')" error-id="bm-error-id_number">
                <input id="bm-id_number" name="id_number" type="text" maxlength="100" class="{{ $ro }}" x-model="f.id_number" @input="changed('id_number')" @blur="touched.id_number=true" {!! $visaState('id_number') !!}>
            </x-erp.field>
        </x-erp.section>

        {{-- Section 4 — Dates & status --}}
        <x-erp.section icon="bi-calendar2-check" title="Dates & Status">
            <x-erp.field label="BMET Date (EC Date)" for="bm-ec_date" required error="error('ec_date')" error-id="bm-error-ec_date">
                <input id="bm-ec_date" name="ec_date" type="date" max="{{ today()->toDateString() }}" class="{{ $inp }}" x-model="f.ec_date" @input="changed('ec_date')" @blur="touched.ec_date=true" required {!! $state('ec_date') !!}>
            </x-erp.field>
            <x-erp.field label="Status" for="bm-status" error="error('status')" error-id="bm-error-status">
                <select id="bm-status" name="status" class="{{ $inp }}" x-model="f.status" @change="changed('status')" {!! $state('status') !!}>
                    <option value="auto">Automatic from EC details</option>
                    @foreach(\App\Models\BmetEntry::STATUSES as $keyStatus=>$labelStatus)<option value="{{ $keyStatus }}">{{ $labelStatus }}</option>@endforeach
                </select>
                {{-- Only for "Automatic": show what it resolves to. --}}
                <p x-show="f.status === 'auto'" x-cloak class="mt-1 text-xs text-slate-500">Resolves to: <strong class="font-semibold text-slate-700" x-text="statusPreview().charAt(0).toUpperCase() + statusPreview().slice(1)"></strong></p>
            </x-erp.field>
            {{-- Tracked one-year validity (display only, not saved); red when within 30 days. --}}
            <x-erp.field label="Valid Until" for="bm-valid_until" auto>
                <input id="bm-valid_until" type="date" readonly tabindex="-1" x-bind:value="expiry()"
                       x-bind:class="expiringSoon() ? '{{ \App\Support\ErpForm::READONLY_ALERT }}' : '{{ \App\Support\ErpForm::READONLY }}'">
            </x-erp.field>
        </x-erp.section>

        {{-- Section 5 — Additional --}}
        <x-erp.section icon="bi-journal-text" title="Additional Info" cols="2">
            {{-- Filled from the Agent select (single source); read-only so old values are kept as-is. --}}
            <x-erp.field label="Reference" for="bm-reference" auto error="error('reference')" error-id="bm-error-reference">
                <input id="bm-reference" name="reference" type="text" readonly tabindex="-1" x-model="f.reference" placeholder="—" class="{{ \App\Support\ErpForm::READONLY }}">
            </x-erp.field>
            <x-erp.textarea label="Remarks" id="bm-remarks" name="remarks" maxlength="2000" x-model="f.remarks" x-on:input="changed('remarks')"
                            error="error('remarks')" error-id="bm-error-remarks" />
        </x-erp.section>
    </x-erp.modal>
</div>
@push('scripts')
<script>
function bmetModal() {
    const cfg=JSON.parse(document.getElementById('bmet-config').textContent);
    const required={full_name:'Passenger name is required',passport_number:'Passport number is required',ec_date:'Please select a valid date'};
    return {
        f:{},id:null,touched:{},server:{},message:'',loading:false,saving:false,failedLoad:false,closing:false,manualVisa:true,
        suggestions:[],activeSuggestion:-1,looking:false,lookupMessage:'',timer:null,lookupVersion:0,openVersion:0,returnFocus:null,lastReference:'',previousOverflow:'',
        init(){if(cfg.add||cfg.edit)this.$nextTick(()=>this.open(cfg.edit||null));},
        async open(id=null){
            if(this.$refs.dialog.open||this.closing)return;
            this.returnFocus=document.activeElement;this.previousOverflow=document.body.style.overflow;
            this.id=id;this.f=Object.fromEntries(cfg.fields.map(k=>[k,'']));this.f.status='auto';this.touched={};this.server={};this.message='';this.suggestions=[];this.lookupMessage='';this.failedLoad=false;this.manualVisa=true;this.lastReference='';
            this.lookupVersion++;const version=++this.openVersion;
            this.$refs.dialog.showModal();document.body.style.overflow='hidden';
            if(id){this.loading=true;try{const r=await fetch(cfg.base+'/'+id,{headers:{Accept:'application/json'}});if(!r.ok)throw Error('Unable to load this entry. Close and try again.');const data=await r.json();if(version===this.openVersion){this.f=Object.assign(this.f,data);this.f.agent_id=this.f.agent_id??'';this.lastReference=this.selectedAgent()?.name||'';}}catch(e){if(version===this.openVersion){this.message=e.message;this.failedLoad=true;}}finally{if(version===this.openVersion)this.loading=false;}}
            if(version===this.openVersion)this.$nextTick(()=>this.$refs.dialog.querySelector('[name="full_name"]')?.focus());
        },
        close(){if(this.saving||this.closing)return;this.openVersion++;this.lookupVersion++;clearTimeout(this.timer);this.loading=false;this.looking=false;this.closing=true;
            setTimeout(()=>{this.$refs.dialog.close();this.closing=false;document.body.style.overflow=this.previousOverflow;this.returnFocus?.focus();},window.matchMedia('(prefers-reduced-motion: reduce)').matches?0:150);},
        changed(k){this.touched[k]=true;delete this.server[k];},
        errors(){const e={};for(const [k,message] of Object.entries(required))if(!String(this.f[k]??'').trim())e[k]=message;
            if(this.f.ec_date&&this.f.ec_date>cfg.today)e.ec_date='Date cannot be in the future';
            for(const k of ['full_name','father_name','passport_number','visa_number','id_number','ec_number'])if(String(this.f[k]??'').length>100)e[k]='Use no more than 100 characters';
            return e;},
        error(k){return this.server[k]?.[0]||(this.touched[k]?this.errors()[k]:'')||'';},
        expiry(){if(!this.f.ec_date)return '';const [y,m,d]=this.f.ec_date.split('-').map(Number);const lastDay=new Date(Date.UTC(y+1,m,0)).getUTCDate();return new Date(Date.UTC(y+1,m-1,Math.min(d,lastDay))).toISOString().slice(0,10);},
        statusPreview(){let status=this.f.status||'auto';if(status==='auto')status=String(this.f.ec_number||'').trim()?'cleared':'pending';return status==='cleared'&&this.expiry()&&this.expiry()<cfg.today?'expired':status;},
        expiringSoon(){const days=(Date.parse(this.expiry())-Date.parse(cfg.today))/86400000;return this.statusPreview()==='cleared'&&days>=0&&days<30;},
        selectedAgent(){return cfg.agents.find(a=>String(a.id)===String(this.f.agent_id));},
        agentChanged(){const agent=this.selectedAgent();if(!this.f.reference||this.f.reference===this.lastReference){this.f.reference=agent?.name||'';this.changed('reference');}this.lastReference=agent?.name||'';},
        // Passport auto-fill now comes from erp.partials._erp-autofill (all ERP modules, HR fallback).
        async save(){if(this.saving||this.failedLoad)return;Object.keys(required).forEach(k=>this.touched[k]=true);const first=Object.keys(this.errors())[0];if(first){this.$refs.dialog.querySelector('[name="'+first+'"]')?.focus();return;}this.saving=true;this.message='';this.server={};
            try{const r=await fetch(cfg.base+(this.id?'/'+this.id:''),{method:this.id?'PUT':'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':cfg.csrf},body:JSON.stringify(this.f)});let data;try{data=await r.json();}catch{throw Error('Unable to save. Refresh the page and try again.');}if(r.status===422){this.server=data.errors||{};this.message='Please fix the highlighted fields.';this.$nextTick(()=>this.$refs.dialog.querySelector('[aria-invalid="true"]')?.focus());return;}if(!r.ok)throw Error(data.message||'Unable to save. Please try again.');const url=new URL(window.location.href);url.searchParams.delete('add');url.searchParams.delete('edit');window.location.assign(url.href);}catch(e){this.message=e.message||'Unable to save. Please try again.';}finally{this.saving=false;}}
    };
}
</script>
@endpush
