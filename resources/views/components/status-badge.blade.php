@props(['status', 'size' => ''])

@php
$map = [
    // agency / subscription
    'active'       => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Active'],
    'trial'        => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Trial'],
    'expired'      => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => 'Expired'],
    'suspended'    => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Suspended'],
    'inactive'     => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => 'Inactive'],
    // embassy list
    'draft'        => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Draft'],
    'finalized'    => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Finalized'],
    'printed'      => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Printed'],
    'cancelled'    => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => 'Cancelled'],
    // hr status
    'blacklisted'  => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Blacklisted'],
    'listed'       => ['bg' => '#ede9fe', 'color' => '#5b21b6', 'label' => 'Listed'],
    // generic
    'pending'      => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Pending'],
    'paid'         => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Paid'],
    'unpaid'       => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Unpaid'],
];
$s = $map[strtolower($status)] ?? ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => ucfirst($status)];
$fs = $size === 'sm' ? 'font-size:.7rem;' : ($size === 'lg' ? 'font-size:.85rem;' : 'font-size:.75rem;');
@endphp

<span style="background:{{ $s['bg'] }};color:{{ $s['color'] }};{{ $fs }}padding:2px 8px;border-radius:20px;font-weight:600;white-space:nowrap;">
    {{ $s['label'] }}
</span>
