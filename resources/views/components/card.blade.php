@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'actions' => null,
    'class' => '',
    'bodyClass' => '',
])

<div {{ $attributes->merge(['class' => 'card app-card '.$class]) }}>
    @if($title || $subtitle || $icon || $actions)
        <div class="card-header bg-white border-0 d-flex align-items-start justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3">
                @if($icon)
                    <span class="app-icon">
                        <i class="bi bi-{{ $icon }}"></i>
                    </span>
                @endif
                <div>
                    @if($title)
                        <h5 class="card-title mb-1">{{ $title }}</h5>
                    @endif
                    @if($subtitle)
                        <p class="card-subtitle text-muted mb-0">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @if($actions)
                <div>{{ $actions }}</div>
            @endif
        </div>
    @endif

    <div class="card-body {{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>
