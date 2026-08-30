@extends('layouts.agency-app')
@section('title', 'Smart Notes')
@section('page-title', 'Smart Notes')

@php
    $inputCls = 'h-10 w-full rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400';
    $areaCls  = 'w-full rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400';

    $priorityTone = ['urgent' => 'red', 'high' => 'amber', 'medium' => 'brand', 'low' => 'slate'];

    $tabs = [
        'active'    => ['label' => 'Active',    'icon' => 'bi-journal-text'],
        'reminders' => ['label' => 'Reminders', 'icon' => 'bi-alarm'],
        'archived'  => ['label' => 'Archived',  'icon' => 'bi-archive'],
        'trash'     => ['label' => 'Trash',     'icon' => 'bi-trash'],
    ];
    $notesUrl = url('/notes');
@endphp

@section('content')
<div x-data="notesPage('{{ $notesUrl }}')" class="mx-auto max-w-6xl">

    <x-ui.page-header title="Smart Notes" subtitle="Notes & reminders for your agency" icon="bi-journal-text">
        <x-slot:actions>
            <x-ui.button type="button" x-on:click="openCreate()" class="cursor-pointer"><i class="bi bi-plus-lg"></i> New Note</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- KPI cards --}}
    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <x-ui.card class="p-4">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50 text-lg text-brand-600"><i class="bi bi-journal-text"></i></span>
                <div><div class="text-2xl font-extrabold leading-none text-slate-900">{{ $totalNotes }}</div><div class="mt-1 text-xs font-semibold text-slate-500">Total Notes</div></div>
            </div>
        </x-ui.card>
        <x-ui.card class="p-4">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-amber-50 text-lg text-amber-600"><i class="bi bi-pin-angle-fill"></i></span>
                <div><div class="text-2xl font-extrabold leading-none text-slate-900">{{ $pinnedCnt }}</div><div class="mt-1 text-xs font-semibold text-slate-500">Pinned</div></div>
            </div>
        </x-ui.card>
        <x-ui.card class="p-4">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-violet-50 text-lg text-violet-600"><i class="bi bi-alarm"></i></span>
                <div><div class="text-2xl font-extrabold leading-none text-slate-900">{{ $reminderCnt }}</div><div class="mt-1 text-xs font-semibold text-slate-500">Upcoming Reminders</div></div>
            </div>
        </x-ui.card>
    </div>

    {{-- Tabs --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach($tabs as $key => $tab)
            <a href="{{ route('notes.index', ['view' => $key]) }}"
               @class([
                   'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold transition',
                   'bg-brand-600 text-white shadow-sm' => $view === $key,
                   'border border-slate-200 bg-white text-slate-600 hover:border-brand-200 hover:text-brand-700' => $view !== $key,
               ])>
                <i class="bi {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
            </a>
        @endforeach
    </div>

    {{-- Filters (hidden on trash) --}}
    @if($view !== 'trash')
        <form method="GET" action="{{ route('notes.index') }}" class="mb-4 flex flex-wrap items-center gap-2">
            <input type="hidden" name="view" value="{{ $view }}">
            <select name="category" onchange="this.form.submit()" class="h-9 rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400">
                <option value="">All Categories</option>
                @foreach($categories as $k => $label)
                    <option value="{{ $k }}" {{ ($filters['category'] ?? '') === $k ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="priority" onchange="this.form.submit()" class="h-9 rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400">
                <option value="">All Priority</option>
                @foreach($priorities as $k => $label)
                    <option value="{{ $k }}" {{ ($filters['priority'] ?? '') === $k ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()" class="h-9 rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400">
                <option value="">All Status</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
            <select name="sort" onchange="this.form.submit()" class="h-9 rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400">
                <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest first</option>
                <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
                <option value="priority" {{ ($filters['sort'] ?? '') === 'priority' ? 'selected' : '' }}>Priority</option>
                <option value="reminder" {{ ($filters['sort'] ?? '') === 'reminder' ? 'selected' : '' }}>Reminder date</option>
            </select>
            @if(array_filter($filters ?? []))
                <a href="{{ route('notes.index', ['view' => $view]) }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 hover:border-slate-300"><i class="bi bi-x-lg"></i> Clear</a>
            @endif
        </form>
    @endif

    {{-- Notes grid --}}
    @if($notes->count())
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($notes as $note)
                @php
                    $pTone = $priorityTone[$note->priority] ?? 'slate';
                    $noteData = [
                        'id'          => $note->id,
                        'title'       => $note->title,
                        'body'        => $note->body,
                        'category'    => $note->category,
                        'priority'    => $note->priority,
                        'status'      => $note->status,
                        'is_private'  => (bool) $note->is_private,
                        'reminder_at' => optional($note->reminder_at)->format('Y-m-d\TH:i'),
                    ];
                @endphp
                <x-ui.card class="flex flex-col p-4 {{ $note->pinned && $view !== 'trash' ? 'ring-1 ring-amber-200' : '' }}">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <h3 class="text-sm font-bold text-slate-800 {{ $note->status === 'completed' ? 'line-through text-slate-400' : '' }}">{{ $note->title }}</h3>
                        @if($note->pinned && $view !== 'trash')<i class="bi bi-pin-angle-fill shrink-0 text-amber-500" title="Pinned"></i>@endif
                    </div>

                    @if($note->body)
                        <p class="mb-3 whitespace-pre-line text-xs leading-relaxed text-slate-500">{{ \Illuminate\Support\Str::limit($note->body, 220) }}</p>
                    @endif

                    <div class="mb-3 flex flex-wrap items-center gap-1.5">
                        <x-ui.badge tone="slate">{{ $note->categoryLabel() }}</x-ui.badge>
                        <x-ui.badge :tone="$pTone">{{ $note->priorityLabel() }}</x-ui.badge>
                        @if($note->status === 'completed')<x-ui.badge tone="green"><i class="bi bi-check-lg"></i> Done</x-ui.badge>@endif
                        @if($note->is_private)<x-ui.badge tone="violet"><i class="bi bi-lock"></i> Private</x-ui.badge>@endif
                    </div>

                    @if($note->reminder_at)
                        <div class="mb-3 flex items-center gap-1.5 text-xs {{ $note->reminder_at->isPast() ? 'text-rose-600' : 'text-slate-500' }}">
                            <i class="bi bi-alarm"></i> {{ $note->reminder_at->format('d M Y, H:i') }}
                        </div>
                    @endif

                    <div class="mt-auto flex items-center justify-end gap-1 border-t border-slate-100 pt-2">
                        @if($view === 'trash')
                            <form method="POST" action="{{ route('notes.restore', $note->id) }}">@csrf
                                <button class="grid h-7 w-7 place-items-center rounded-lg text-emerald-600 hover:bg-emerald-50" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                            </form>
                            <form method="POST" action="{{ route('notes.force-delete', $note->id) }}" onsubmit="return confirm('Permanently delete this note? This cannot be undone.')">@csrf @method('DELETE')
                                <button class="grid h-7 w-7 place-items-center rounded-lg text-rose-600 hover:bg-rose-50" title="Delete permanently"><i class="bi bi-trash-fill"></i></button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('notes.pin', $note) }}">@csrf
                                <button class="grid h-7 w-7 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" title="{{ $note->pinned ? 'Unpin' : 'Pin' }}"><i class="bi {{ $note->pinned ? 'bi-pin-fill' : 'bi-pin-angle' }}"></i></button>
                            </form>
                            <form method="POST" action="{{ route('notes.complete', $note) }}">@csrf
                                <button class="grid h-7 w-7 place-items-center rounded-lg text-emerald-600 hover:bg-emerald-50" title="{{ $note->status === 'completed' ? 'Mark pending' : 'Mark complete' }}"><i class="bi {{ $note->status === 'completed' ? 'bi-arrow-counterclockwise' : 'bi-check2-circle' }}"></i></button>
                            </form>
                            <button type="button"
                                x-on:click="openEdit({{ \Illuminate\Support\Js::from($noteData) }})"
                                class="grid h-7 w-7 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" title="Edit"><i class="bi bi-pencil"></i></button>
                            <form method="POST" action="{{ route('notes.archive', $note) }}">@csrf
                                <button class="grid h-7 w-7 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" title="{{ $note->isArchived() ? 'Unarchive' : 'Archive' }}"><i class="bi {{ $note->isArchived() ? 'bi-archive-fill' : 'bi-archive' }}"></i></button>
                            </form>
                            <form method="POST" action="{{ route('notes.destroy', $note) }}" onsubmit="return confirm('Move this note to Trash?')">@csrf @method('DELETE')
                                <button class="grid h-7 w-7 place-items-center rounded-lg text-rose-600 hover:bg-rose-50" title="Move to Trash"><i class="bi bi-trash"></i></button>
                            </form>
                        @endif
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <div class="mt-5">{{ $notes->links() }}</div>
    @else
        <x-ui.empty icon="{{ $tabs[$view]['icon'] }}" title="No notes here"
            :actionUrl="null" />
        <p class="-mt-4 text-center text-sm text-slate-400">
            @if($view === 'trash') Trash is empty.
            @elseif($view === 'archived') No archived notes.
            @elseif($view === 'reminders') No notes with upcoming reminders.
            @else Click “New Note” to create your first note. @endif
        </p>
    @endif

    {{-- ── Create / Edit modal ─────────────────────────────── --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-8" x-on:click.self="open = false" x-on:keydown.escape.window="open = false">
        <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl" x-transition>
            <form method="POST" x-bind:action="formAction">
                @csrf
                <input type="hidden" name="_method" x-bind:value="mode === 'edit' ? 'PUT' : 'POST'">

                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-bold text-slate-800" x-text="mode === 'edit' ? 'Edit Note' : 'New Note'"></h2>
                    <button type="button" x-on:click="open = false" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100"><i class="bi bi-x-lg"></i></button>
                </div>

                <div class="space-y-4 p-5">
                    <x-ui.field label="Title" name="title" required>
                        <input type="text" name="title" x-model="form.title" maxlength="200" placeholder="Note title" class="{{ $inputCls }}">
                    </x-ui.field>
                    <x-ui.field label="Details" name="body">
                        <textarea name="body" x-model="form.body" rows="4" maxlength="5000" placeholder="Write your note…" class="{{ $areaCls }}"></textarea>
                    </x-ui.field>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.field label="Category" name="category">
                            <select name="category" x-model="form.category" class="{{ $inputCls }}">
                                @foreach($categories as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                            </select>
                        </x-ui.field>
                        <x-ui.field label="Priority" name="priority">
                            <select name="priority" x-model="form.priority" class="{{ $inputCls }}">
                                @foreach($priorities as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                            </select>
                        </x-ui.field>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.field label="Reminder" name="reminder_at" hint="Optional date & time.">
                            <input type="datetime-local" name="reminder_at" x-model="form.reminder_at" class="{{ $inputCls }}">
                        </x-ui.field>
                        <x-ui.field label="Status" name="status">
                            <select name="status" x-model="form.status" class="{{ $inputCls }}">
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                            </select>
                        </x-ui.field>
                    </div>
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <input type="checkbox" name="is_private" value="1" x-model="form.is_private" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm text-slate-700">Private note</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3">
                    <x-ui.button type="button" variant="secondary" x-on:click="open = false" class="cursor-pointer">Cancel</x-ui.button>
                    <x-ui.button type="submit" class="cursor-pointer"><i class="bi bi-floppy"></i> <span x-text="mode === 'edit' ? 'Update' : 'Create'"></span></x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function notesPage(baseUrl) {
        return {
            open: false,
            mode: 'create',
            editId: null,
            form: { title: '', body: '', category: 'general', priority: 'medium', status: 'pending', is_private: false, reminder_at: '' },
            get formAction() { return this.mode === 'edit' ? baseUrl + '/' + this.editId : baseUrl; },
            openCreate() {
                this.mode = 'create';
                this.editId = null;
                this.form = { title: '', body: '', category: 'general', priority: 'medium', status: 'pending', is_private: false, reminder_at: '' };
                this.open = true;
            },
            openEdit(n) {
                this.mode = 'edit';
                this.editId = n.id;
                this.form = {
                    title: n.title ?? '', body: n.body ?? '',
                    category: n.category ?? 'general', priority: n.priority ?? 'medium',
                    status: n.status ?? 'pending', is_private: !!n.is_private,
                    reminder_at: n.reminder_at ?? '',
                };
                this.open = true;
            },
        };
    }
</script>
@endpush
@endsection
