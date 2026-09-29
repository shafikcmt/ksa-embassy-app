{{--
    ERP form field: label (+ red * / "(auto)"), the control in the slot, and a
    small red error line. Errors come from either source:
      error="fieldError('x')"  → Alpine expression (fetch/JSON modals)
      name="x"                 → server $errors bag (classic POST forms)
    error-id keeps the id the control's aria-describedby already points at.
--}}
@props([
    'label'    => '',
    'for'      => null,
    'required' => false,
    'auto'     => false,
    'error'    => null,
    'errorId'  => null,
    'name'     => null,
])
<div {{ $attributes }}>
    <label @if($for) for="{{ $for }}" @endif class="mb-1.5 block text-[13px] font-medium text-slate-700">
        {{ $label }}@if($auto) <span class="font-normal text-slate-400">(auto)</span>@endif @if($required)<span class="text-red-500" aria-hidden="true">*</span>@endif
    </label>

    {{ $slot }}

    @if($error)
        <p @if($errorId) id="{{ $errorId }}" @endif class="{{ \App\Support\ErpForm::ERROR }}" x-show="{{ $error }}" x-cloak x-text="{{ $error }}" role="alert"></p>
    @endif
    @if($name && $errors->has($name))
        <p class="{{ \App\Support\ErpForm::ERROR }}" role="alert">{{ $errors->first($name) }}</p>
    @endif
</div>
