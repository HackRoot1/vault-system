@props([
    'label' => null,
    'name',
    'type' => 'text',
    'id' => null,
    'placeholder' => null,
    'help' => null,
    'required' => false,
])

@php
    $fieldId = $id ?: $name;
@endphp

<div class="mb-3">
    @if($label)
        <label for="{{ $fieldId }}" class="form-label">{{ $label }}</label>
    @endif
    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        placeholder="{{ $placeholder }}"
        @required($required)
        {{ $attributes->merge(['class' => 'form-control form-control-lg']) }}
    >
    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    <div class="invalid-feedback" data-field-error="{{ $name }}"></div>
</div>
