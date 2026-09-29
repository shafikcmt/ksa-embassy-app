{{--
    ERP Remarks/notes textarea: spans the full section width, 3 rows, resize-y,
    same border/radius/focus as other inputs. All extra attributes (id, name,
    x-model, maxlength, …) go on the <textarea>. `error` / `name` work as in
    x-erp.field; with `error` the border turns red while that expression is truthy.
--}}
@props([
    'label'   => 'Remarks',
    'rows'    => 3,
    'error'   => null,
    'errorId' => null,
    'name'    => null,
    'value'   => null,
])
@php
    $serverError = $name && $errors->has($name);
@endphp
<div class="col-span-full">
    <label @if($attributes->has('id')) for="{{ $attributes->get('id') }}" @endif class="mb-1.5 block text-[13px] font-medium text-slate-700">{{ $label }}</label>
    <textarea rows="{{ $rows }}" @if($name) name="{{ $name }}" @endif
        @if($error)
            x-bind:class="({{ $error }}) ? '{{ \App\Support\ErpForm::BORDER_ERROR }}' : '{{ \App\Support\ErpForm::BORDER_OK }}'"
            x-bind:aria-invalid="({{ $error }}) ? 'true' : 'false'"
            @if($errorId) aria-describedby="{{ $errorId }}" @endif
        @endif
        {{ $attributes->class([\App\Support\ErpForm::TEXTAREA, \App\Support\ErpForm::BORDER_OK => ! $error && ! $serverError, \App\Support\ErpForm::BORDER_ERROR => ! $error && $serverError]) }}>{{ $value }}</textarea>
    @if($error)
        <p @if($errorId) id="{{ $errorId }}" @endif class="{{ \App\Support\ErpForm::ERROR }}" x-show="{{ $error }}" x-cloak x-text="{{ $error }}" role="alert"></p>
    @endif
    @if($serverError)
        <p class="{{ \App\Support\ErpForm::ERROR }}" role="alert">{{ $errors->first($name) }}</p>
    @endif
</div>
