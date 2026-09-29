{{--
    Searchable single-select for ERP "Reference" (agent names). The value is the
    plain name string, so the DB column and validation stay as they are.

    Binding (pick one):
      x-model="f.reference"   → fetch modals / Alpine forms (via x-modelable)
      name="reference"        → classic POST: the ONLY named input is a hidden one
      value="…"               → initial value for classic forms (e.g. old('reference'))
    Both x-model and name can be combined (classic Edit modals).

    Options: active agents only. A saved value that matches an inactive agent
    shows "Name (inactive)"; one that matches no agent shows "Name (old)" — both
    are kept as the selected option so saving unchanged never loses them.

    The panel is position:fixed and opens upward when there's no room below, so
    it is never clipped by the scrolling modal body.
--}}
@props([
    'agents'      => [],
    'id'          => null,
    'name'        => null,
    'value'       => '',
    'placeholder' => 'Select agent',
    'error'       => null,
])
<div x-data="erpSelectSearch(@js(array_values($agents)), @js((string) $value), @js($placeholder))" x-modelable="value"
     x-on:keydown.escape="if (open) { $event.preventDefault(); $event.stopPropagation(); close(true) }" x-on:click.outside="close()"
     {{ $attributes->class('relative') }}>
    @if($name)
        {{-- The only submitted field. x-model also picks up legacy autofill (sets value + fires input). --}}
        <input type="hidden" name="{{ $name }}" x-model="value">
    @endif

    <button type="button" @if($id) id="{{ $id }}" @endif x-ref="trigger"
            x-on:click="toggle()" x-on:keydown.arrow-down.prevent="openPanel()" x-on:keydown.arrow-up.prevent="openPanel()"
            aria-haspopup="listbox" x-bind:aria-expanded="open ? 'true' : 'false'"
            @if($error) x-bind:aria-invalid="({{ $error }}) ? 'true' : 'false'" @endif
            class="{{ \App\Support\ErpForm::INPUT }} flex items-center justify-between gap-2 pr-3 text-left"
            @if($error)
                x-bind:class="({{ $error }}) ? '{{ \App\Support\ErpForm::BORDER_ERROR }}' : '{{ \App\Support\ErpForm::BORDER_OK }}'"
            @else
                x-bind:class="'{{ \App\Support\ErpForm::BORDER_OK }}'"
            @endif>
        <span class="truncate" x-bind:class="value ? 'text-slate-800' : 'text-slate-400'" x-text="value ? label(value) : placeholder"></span>
        <i class="bi bi-chevron-down shrink-0 text-xs text-slate-400 transition" x-bind:class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
    </button>

    <div x-show="open" x-cloak x-bind:style="panelStyle"
         class="fixed z-[90] flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl">
        <div class="shrink-0 border-b border-slate-100 p-2">
            {{-- Search box: deliberately NO name attribute (never submitted). --}}
            <input type="text" x-ref="search" x-model="q" autocomplete="off" spellcheck="false" placeholder="Search agent…" aria-label="Search agent"
                   x-on:input="active = 0"
                   x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
                   x-on:keydown.enter.prevent="pickActive()" x-on:keydown.tab="close()"
                   class="block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
        </div>
        <ul role="listbox" x-ref="list" class="min-h-0 flex-1 overflow-y-auto py-1">
            <template x-for="(o, i) in options()" :key="'o' + i + o.value">
                <li role="option" x-bind:aria-selected="o.value === value ? 'true' : 'false'"
                    x-on:mousedown.prevent="pick(o.value)" x-on:mouseenter="active = i"
                    class="flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-sm"
                    x-bind:class="[active === i ? 'bg-brand-50' : '', o.value === '' ? 'text-slate-400' : 'text-slate-700']">
                    <span class="truncate" x-text="o.label"></span>
                    <i class="bi bi-check2 text-brand-600" x-show="o.value !== '' && o.value === value" aria-hidden="true"></i>
                </li>
            </template>
            <li x-show="q && !options().some(o => o.value !== '')" class="px-3 py-2 text-sm text-slate-400">No matching agent</li>
        </ul>
    </div>
</div>

@once
@push('scripts')
<script>
    function erpSelectSearch(agents, initial, placeholder) {
        const active = agents.filter(a => a.active).map(a => a.name);
        const inactive = agents.filter(a => !a.active).map(a => a.name);
        return {
            value: initial || '', placeholder, open: false, q: '', active: 0, panelStyle: '',
            label(v) {
                if (active.includes(v)) return v;
                return v + (inactive.includes(v) ? ' (inactive)' : ' (old)');
            },
            options() {
                const q = this.q.trim().toLowerCase();
                const opts = [{ value: '', label: this.placeholder }];
                // A saved/filled value that isn't an active agent stays selectable.
                if (this.value && !active.includes(this.value)) opts.push({ value: this.value, label: this.label(this.value) });
                for (const n of active) opts.push({ value: n, label: n });
                return q ? opts.filter(o => o.value !== '' && o.label.toLowerCase().includes(q)) : opts;
            },
            place() {
                const r = this.$refs.trigger.getBoundingClientRect();
                const vh = window.innerHeight, gap = 4, want = 288;
                const below = vh - r.bottom - 8, above = r.top - 8;
                const up = below < Math.min(want, 220) && above > below;
                const maxH = Math.max(140, Math.min(want, up ? above : below));
                this.panelStyle = `left:${r.left}px;width:${Math.max(r.width, 220)}px;max-height:${maxH}px;`
                    + (up ? `bottom:${vh - r.top + gap}px;` : `top:${r.bottom + gap}px;`);
            },
            openPanel() {
                if (this.open) return;
                this.q = '';
                this.place();
                this.open = true;
                const i = this.options().findIndex(o => o.value === this.value);
                this.active = i < 0 ? 0 : i;
                this._reposition = () => this.open && this.place();
                window.addEventListener('scroll', this._reposition, true);
                window.addEventListener('resize', this._reposition);
                this.$nextTick(() => { this.$refs.search.focus(); this.scrollActive(); });
            },
            close(refocus = false) {
                if (!this.open) return;
                this.open = false;
                window.removeEventListener('scroll', this._reposition, true);
                window.removeEventListener('resize', this._reposition);
                if (refocus) this.$refs.trigger.focus();
            },
            toggle() { this.open ? this.close(true) : this.openPanel(); },
            move(step) {
                const n = this.options().length;
                if (!n) return;
                this.active = (this.active + step + n) % n;
                this.scrollActive();
            },
            scrollActive() { this.$nextTick(() => this.$refs.list.querySelectorAll('[role=option]')[this.active]?.scrollIntoView({ block: 'nearest' })); },
            pickActive() { const o = this.options()[this.active]; if (o) this.pick(o.value); },
            pick(v) {
                this.value = v;
                this.close(true);
                // Let the host form react like a native control (e.g. clear a server error).
                this.$nextTick(() => this.$root.dispatchEvent(new Event('change', { bubbles: true })));
            },
        };
    }
</script>
@endpush
@endonce
