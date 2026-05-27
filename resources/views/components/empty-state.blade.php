@props(['icon' => 'bi-inbox', 'title' => 'Nothing here yet', 'message' => '', 'actionUrl' => null, 'actionLabel' => null, 'size' => 'md'])

@php
$iconSize  = $size === 'sm' ? 'fs-2' : 'fs-1';
$padClass  = $size === 'sm' ? 'py-3' : 'py-5';
@endphp

<div class="text-center text-muted {{ $padClass }}">
    <i class="bi {{ $icon }} {{ $iconSize }} opacity-25 d-block mb-2"></i>
    <div class="fw-semibold" style="font-size:.875rem;">{{ $title }}</div>
    @if($message)
        <div style="font-size:.78rem;" class="mt-1">{{ $message }}</div>
    @endif
    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="btn btn-sm btn-primary mt-3">{{ $actionLabel }}</a>
    @endif
</div>
