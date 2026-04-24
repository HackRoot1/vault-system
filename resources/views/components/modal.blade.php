@props([
    'id',
    'title',
    'size' => null,
    'static' => false,
])

<div
    class="modal fade"
    id="{{ $id }}"
    tabindex="-1"
    @if($static) data-bs-backdrop="static" data-bs-keyboard="false" @endif
>
    <div class="modal-dialog {{ $size ? 'modal-'.$size : '' }}">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                @unless($static)
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                @endunless
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
