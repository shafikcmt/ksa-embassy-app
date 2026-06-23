@props([
    'label'    => null,
    'name'     => null,   // used to bind the <label for> and pull the validation error
    'required' => false,
    'hint'     => null,
])

<div {{ $attributes->merge(['class' => '']) }}>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="mb-1 block text-xs font-semibold text-slate-600">
            {{ $label }}@if($required)<span class="ml-0.5 text-rose-500">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if($name && $errors->has($name))
        <p class="mt-1 text-xs font-medium text-rose-600">{{ $errors->first($name) }}</p>
    @elseif($hint)
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif
</div>
