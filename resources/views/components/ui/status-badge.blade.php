@props([
    'status' => '',
])

@php
    // Maps a domain status to a badge tone. Covers HR, subscription & embassy-list statuses.
    $map = [
        'active'      => 'green',
        'finalized'   => 'green',
        'trial'       => 'brand',
        'printed'     => 'cyan',
        'draft'       => 'amber',
        'listed'      => 'violet',
        'blacklisted' => 'red',
        'suspended'   => 'red',
        'inactive'    => 'slate',
        'expired'     => 'slate',
        'cancelled'   => 'slate',
    ];
    $tone = $map[$status] ?? 'slate';
    $dot  = [
        'green' => 'bg-emerald-500', 'brand' => 'bg-brand-500', 'cyan' => 'bg-cyan-500',
        'amber' => 'bg-amber-500', 'violet' => 'bg-violet-500', 'red' => 'bg-rose-500',
        'slate' => 'bg-slate-400',
    ][$tone];
@endphp

<x-ui.badge :tone="$tone" {{ $attributes }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
    {{ ucfirst($status) }}
</x-ui.badge>
