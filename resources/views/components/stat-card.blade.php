@props([
    'icon'    => 'bi-bar-chart',
    'label'   => '',
    'value'   => '',
    'sub'     => null,
    'href'    => null,
    'tone'    => 'slate',   // slate | blue | green | amber | red  (icon tint)
    'subTone' => 'muted',   // muted | green | amber | red          (sub text colour)
])

@php
    $tones = [
        'slate' => ['#eef2f7', '#475569'],
        'blue'  => ['#e8effd', '#1d4ed8'],
        'green' => ['#e7f8f0', '#047857'],
        'amber' => ['#fdf4e3', '#b45309'],
        'red'   => ['#fdeced', '#be123c'],
    ];
    [$tbg, $tfg] = $tones[$tone] ?? $tones['slate'];

    $subColors = ['muted' => '#94a3b8', 'green' => '#059669', 'amber' => '#d97706', 'red' => '#dc2626'];
    $subColor  = $subColors[$subTone] ?? $subColors['muted'];

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'stat-card' . ($href ? ' stat-link' : '')]) }}>
    <div class="stat-top">
        <span class="stat-ic" style="background:{{ $tbg }};color:{{ $tfg }};"><i class="bi {{ $icon }}"></i></span>
        @if($href)<i class="bi bi-chevron-right stat-go"></i>@endif
    </div>
    <div class="stat-value">{{ $value }}</div>
    <div class="stat-label">{{ $label }}</div>
    @if(!is_null($sub) && $sub !== '')<div class="stat-sub" style="color:{{ $subColor }};">{{ $sub }}</div>@endif
</{{ $tag }}>
