@props([
    'status' => '',
    'label'  => null,   // optional clean display text (e.g. AttendanceRecord::statusLabel())
])

@php
    // Maps a domain status to a badge tone. Covers HR, subscription, embassy-list
    // and attendance statuses.
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
        // Attendance (H3b): present/late/absent are the anchors; the rest sit in
        // the same visual family (green good, amber tardy, red bad, muted for off-days).
        'present'  => 'green',
        'late'     => 'amber',
        'half_day' => 'violet',
        'absent'   => 'red',
        'excused'  => 'cyan',
        'on_leave' => 'brand',
        'weekend'  => 'slate',
        'holiday'  => 'slate',
        'pending'  => 'slate',
    ];
    $tone = $map[$status] ?? 'slate';
    $dot  = [
        'green' => 'bg-emerald-500', 'brand' => 'bg-brand-500', 'cyan' => 'bg-cyan-500',
        'amber' => 'bg-amber-500', 'violet' => 'bg-violet-500', 'red' => 'bg-rose-500',
        'slate' => 'bg-slate-400',
    ][$tone];

    // Clean default text: underscores → spaces, first letter capped ('half_day' → 'Half day').
    $text = $label ?? ucfirst(str_replace('_', ' ', (string) $status));
@endphp

<x-ui.badge :tone="$tone" {{ $attributes }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
    {{ $text }}
</x-ui.badge>
