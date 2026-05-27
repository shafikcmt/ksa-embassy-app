@props(['alerts' => []])

@if(count($alerts))
<div class="mb-3">
    @foreach($alerts as $alert)
    @php
    $bgMap = ['danger' => '#fef2f2', 'warning' => '#fffbeb', 'info' => '#eff6ff', 'success' => '#f0fdf4'];
    $borderMap = ['danger' => '#fca5a5', 'warning' => '#fcd34d', 'info' => '#93c5fd', 'success' => '#86efac'];
    $textMap = ['danger' => '#991b1b', 'warning' => '#78350f', 'info' => '#1e40af', 'success' => '#14532d'];
    $bg = $bgMap[$alert['type']] ?? '#f9fafb';
    $border = $borderMap[$alert['type']] ?? '#d1d5db';
    $text = $textMap[$alert['type']] ?? '#111827';
    @endphp
    <div style="background:{{ $bg }};border:1px solid {{ $border }};border-radius:8px;padding:10px 14px;margin-bottom:8px;display:flex;align-items:flex-start;gap:10px;">
        <i class="bi {{ $alert['icon'] }}" style="color:{{ $text }};margin-top:2px;font-size:1rem;flex-shrink:0;"></i>
        <div style="flex:1;font-size:.82rem;color:{{ $text }};">{!! $alert['message'] !!}</div>
        @if(!empty($alert['action']))
        <a href="{{ $alert['action'] }}" style="font-size:.78rem;color:{{ $text }};white-space:nowrap;text-decoration:underline;font-weight:600;">
            {{ $alert['action_label'] }} →
        </a>
        @endif
    </div>
    @endforeach
</div>
@endif
