@extends('layouts.agency-app')
@section('title', 'Staff Accounts')
@section('page-title', 'Staff Accounts')

@php
    $moduleKeys = array_keys($modules);
    // Re-open the correct modal after a validation redirect.
    $reopenCreate = old('form') === 'create';
    $reopenEditId = old('form') === 'edit' ? (int) old('edit_id') : null;
    $oldModules   = collect(old('modules', []))->values();
    $oldActions   = collect(old('actions', []))->values();
@endphp

@section('content')
<div x-data="{
        create: { open: {{ $reopenCreate ? 'true' : 'false' }} },
        del: { open: false, name: '', action: '' },
        edit: {
            open: {{ $reopenEditId ? 'true' : 'false' }},
            id: {{ $reopenEditId ?? 'null' }},
            name: @js(old('name')),
            email: @js(old('email')),
            is_active: true,
            modules: @js($reopenEditId ? $oldModules : []),
            actions: @js($reopenEditId ? $oldActions : []),
            action: {{ $reopenEditId ? "'".route('staff.update', $reopenEditId)."'" : "''" }}
        },
        openEdit(member) {
            this.edit.open = true;
            this.edit.id = member.id;
            this.edit.name = member.name;
            this.edit.email = member.email;
            this.edit.is_active = member.is_active;
            this.edit.modules = member.modules;
            this.edit.actions = member.actions;
            this.edit.action = member.action;
        }
    }">

    {{-- Header --}}
    <x-ui.page-header
        title="Staff Accounts"
        subtitle="Create staff logins and choose which sections each one can access"
        icon="bi-people">
        <x-slot:actions>
            <x-ui.button type="button" class="cursor-pointer" x-on:click="create.open = true">
                <i class="bi bi-person-plus"></i> New Staff Account
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Stat cards --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <x-ui.stat icon="bi-people-fill" tone="brand" label="Staff Accounts" :value="$staff->count()" />
        <x-ui.stat icon="bi-person-check" tone="green" label="Active" :value="$staff->where('is_active', true)->count()" />
        <x-ui.stat icon="bi-person-dash" tone="slate" label="Inactive" :value="$staff->where('is_active', false)->count()" />
    </div>

    {{-- ── Desktop table ─────────────────────────────────────── --}}
    <x-ui.card class="hidden overflow-hidden lg:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="w-[4%] px-3 py-3">#</th>
                        <th class="px-3 py-3">Name</th>
                        <th class="px-3 py-3">Email</th>
                        <th class="px-3 py-3">Module Access</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($staff as $member)
                        @php $granted = $staffAccess[$member->id] ?? []; @endphp
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-3 py-3 text-slate-400">{{ $loop->iteration }}</td>
                            <td class="px-3 py-3 font-semibold text-slate-800">{{ $member->name }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $member->email }}</td>
                            <td class="px-3 py-3">
                                @if(count($granted) === count($modules))
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700"><i class="bi bi-check-all"></i> All sections</span>
                                @elseif(count($granted) === 0)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700"><i class="bi bi-slash-circle"></i> No access</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($granted as $key)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700">
                                                <i class="bi {{ $modules[$key]['icon'] }}"></i> {{ $modules[$key]['label'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if($member->is_active)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Active</span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Inactive</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-end gap-1 whitespace-nowrap">
                                    <button type="button" title="Edit" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-brand-600 transition-colors hover:bg-brand-50"
                                        x-on:click="openEdit(@js(['id' => $member->id, 'name' => $member->name, 'email' => $member->email, 'is_active' => (bool) $member->is_active, 'modules' => $granted, 'actions' => $staffActions[$member->id] ?? [], 'action' => route('staff.update', $member)]))"><i class="bi bi-pencil"></i></button>
                                    <button type="button" title="Delete" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50"
                                        x-on:click="del.open = true; del.name = @js($member->name); del.action = '{{ route('staff.destroy', $member) }}'"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-0">
                            <x-ui.empty icon="bi-people" title="No staff accounts yet"
                                message="Create a staff login and choose which sections they can open." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- ── Mobile cards ──────────────────────────────────────── --}}
    <div class="space-y-3 lg:hidden">
        @forelse($staff as $member)
            @php $granted = $staffAccess[$member->id] ?? []; @endphp
            <x-ui.card class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate font-semibold text-slate-800">{{ $member->name }}</div>
                        <div class="truncate text-xs text-slate-400">{{ $member->email }}</div>
                    </div>
                    @if($member->is_active)
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Active</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Inactive</span>
                    @endif
                </div>
                <div class="mt-3 flex flex-wrap gap-1">
                    @forelse($granted as $key)
                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700"><i class="bi {{ $modules[$key]['icon'] }}"></i> {{ $modules[$key]['label'] }}</span>
                    @empty
                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">No access</span>
                    @endforelse
                </div>
                @php $btnBase = 'inline-flex h-8 items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50'; @endphp
                <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                    <button type="button" class="flex-1 cursor-pointer {{ $btnBase }}"
                        x-on:click="openEdit(@js(['id' => $member->id, 'name' => $member->name, 'email' => $member->email, 'is_active' => (bool) $member->is_active, 'modules' => $granted, 'actions' => $staffActions[$member->id] ?? [], 'action' => route('staff.update', $member)]))"><i class="bi bi-pencil"></i> Edit</button>
                    <button type="button" class="cursor-pointer {{ $btnBase }}"
                        x-on:click="del.open = true; del.name = @js($member->name); del.action = '{{ route('staff.destroy', $member) }}'"><i class="bi bi-trash text-rose-500"></i></button>
                </div>
            </x-ui.card>
        @empty
            <x-ui.card>
                <x-ui.empty icon="bi-people" title="No staff accounts yet"
                    message="Create a staff login and choose which sections they can open." />
            </x-ui.card>
        @endforelse
    </div>

    {{-- ── Create modal ──────────────────────────────────────── --}}
    <div x-show="create.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
        <div @click="create.open = false" x-show="create.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
        <div x-show="create.open"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl">
            <form method="POST" action="{{ route('staff.store') }}">
                @csrf
                <input type="hidden" name="form" value="create">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-900">New Staff Account</h3>
                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" x-on:click="create.open = false"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.field label="Full name" name="name" required>
                            <input type="text" name="name" value="{{ $reopenCreate ? old('name') : '' }}" required class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400" placeholder="e.g. Karim Hasan">
                        </x-ui.field>
                        <x-ui.field label="Login email" name="email" required>
                            <input type="email" name="email" value="{{ $reopenCreate ? old('email') : '' }}" required class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400" placeholder="staff@example.com">
                        </x-ui.field>
                        <x-ui.field label="Password" name="password" required hint="At least 8 characters">
                            <input type="password" name="password" required class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400" autocomplete="new-password">
                        </x-ui.field>
                        <x-ui.field label="Confirm password" name="password_confirmation" required>
                            <input type="password" name="password_confirmation" required class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400" autocomplete="new-password">
                        </x-ui.field>
                    </div>
                    <div>
                        <div class="mb-2 text-xs font-semibold text-slate-600">Module access</div>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach($modules as $key => $meta)
                                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-slate-200 p-3 transition hover:border-brand-300 hover:bg-brand-50/40 has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50">
                                    <input type="checkbox" name="modules[]" value="{{ $key }}" @checked($reopenCreate && $oldModules->contains($key)) class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    <span class="min-w-0">
                                        <span class="flex items-center gap-1.5 text-sm font-medium text-slate-800"><i class="bi {{ $meta['icon'] }} text-brand-500"></i> {{ $meta['label'] }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-400">{{ $meta['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <div class="mb-2 text-xs font-semibold text-slate-600">Payment Actions</div>
                        <div class="grid grid-cols-1 gap-2">
                            @foreach($actions as $key => $meta)
                                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-slate-200 p-3 transition hover:border-brand-300 hover:bg-brand-50/40 has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50">
                                    <input type="checkbox" name="actions[]" value="{{ $key }}" @checked($reopenCreate && $oldActions->contains($key)) class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    <span class="min-w-0">
                                        <span class="flex items-center gap-1.5 text-sm font-medium text-slate-800"><i class="bi {{ $meta['icon'] }} text-brand-500"></i> {{ $meta['label'] }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-400">{{ $meta['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
                    <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer" x-on:click="create.open = false">Cancel</x-ui.button>
                    <x-ui.button type="submit" size="sm" class="cursor-pointer"><i class="bi bi-check-lg"></i> Create Account</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Edit modal ────────────────────────────────────────── --}}
    <div x-show="edit.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
        <div @click="edit.open = false" x-show="edit.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
        <div x-show="edit.open"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl">
            <form method="POST" :action="edit.action">
                @csrf
                @method('PUT')
                <input type="hidden" name="form" value="edit">
                <input type="hidden" name="edit_id" :value="edit.id">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-900">Edit Staff Account</h3>
                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" x-on:click="edit.open = false"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.field label="Full name" name="name" required>
                            <input type="text" name="name" x-model="edit.name" required class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400">
                        </x-ui.field>
                        <x-ui.field label="Login email" name="email" required>
                            <input type="email" name="email" x-model="edit.email" required class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400">
                        </x-ui.field>
                        <x-ui.field label="New password" name="password" hint="Leave blank to keep current">
                            <input type="password" name="password" class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400" autocomplete="new-password">
                        </x-ui.field>
                        <x-ui.field label="Confirm new password" name="password_confirmation">
                            <input type="password" name="password_confirmation" class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400" autocomplete="new-password">
                        </x-ui.field>
                    </div>
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 p-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" x-model="edit.is_active" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm font-medium text-slate-800">Account is active <span class="font-normal text-slate-400">(uncheck to disable login)</span></span>
                    </label>
                    <div>
                        <div class="mb-2 text-xs font-semibold text-slate-600">Module access</div>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach($modules as $key => $meta)
                                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-slate-200 p-3 transition hover:border-brand-300 hover:bg-brand-50/40 has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50">
                                    <input type="checkbox" name="modules[]" value="{{ $key }}" x-model="edit.modules" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    <span class="min-w-0">
                                        <span class="flex items-center gap-1.5 text-sm font-medium text-slate-800"><i class="bi {{ $meta['icon'] }} text-brand-500"></i> {{ $meta['label'] }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-400">{{ $meta['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <div class="mb-2 text-xs font-semibold text-slate-600">Payment Actions</div>
                        <div class="grid grid-cols-1 gap-2">
                            @foreach($actions as $key => $meta)
                                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-slate-200 p-3 transition hover:border-brand-300 hover:bg-brand-50/40 has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50">
                                    <input type="checkbox" name="actions[]" value="{{ $key }}" x-model="edit.actions" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    <span class="min-w-0">
                                        <span class="flex items-center gap-1.5 text-sm font-medium text-slate-800"><i class="bi {{ $meta['icon'] }} text-brand-500"></i> {{ $meta['label'] }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-400">{{ $meta['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
                    <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer" x-on:click="edit.open = false">Cancel</x-ui.button>
                    <x-ui.button type="submit" size="sm" class="cursor-pointer"><i class="bi bi-check-lg"></i> Save Changes</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Delete dialog ─────────────────────────────────────── --}}
    <div x-show="del.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
        <div @click="del.open = false" x-show="del.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
        <div x-show="del.open"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-50 text-rose-600"><i class="bi bi-exclamation-triangle text-lg"></i></span>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Delete Staff Account</h3>
                    <p class="mt-1 text-sm text-slate-500">Are you sure you want to delete <strong x-text="del.name"></strong>? This login will be removed permanently.</p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer" x-on:click="del.open = false">Cancel</x-ui.button>
                <form :action="del.action" method="POST">
                    @csrf @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm" class="cursor-pointer"><i class="bi bi-trash"></i> Delete Account</x-ui.button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
