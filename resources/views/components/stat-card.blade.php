@props(['label', 'value', 'color' => 'gray', 'icon' => null, 'trend' => null, 'href' => null, 'sub' => null, 'hero' => false, 'target' => null])

@php
    // Map role/color to accent (used for icon tinting when not the role gradient)
    $tints = [
        'indigo' => '#6366f1', 'green' => '#16a34a', 'blue' => '#2563eb', 'red' => '#dc2626',
        'yellow' => '#d97706', 'orange' => '#ea580c', 'purple' => '#7c3aed', 'teal' => '#0d9488',
        'cyan' => '#0891b2', 'gray' => '#6b7280',
    ];
    $tint = $tints[$color] ?? 'var(--role-accent)';
    $soft = $color === 'brand' ? 'var(--role-accent-soft)' : $tint . '1a';
@endphp

@if($hero)
    {{-- Kanakku hero gradient stat card --}}
    <div class="ks-hero">
        <p class="ks-hero-label">{{ $label }}</p>
        <p class="ks-hero-value">{{ $value }}</p>
        @if($sub)
            <p style="font-size:12px;color:rgba(255,255,255,0.8);margin:0 0 14px;position:relative;z-index:1;">{{ $sub }}</p>
        @endif
        @if($href)
            <a href="{{ $href }}" class="ks-hero-btn">
                <i class="ti ti-arrow-right" style="font-size:14px;"></i> View Details
            </a>
        @endif
    </div>
@else
    {{-- Kanakku icon stat card --}}
    @if($href)
        <a href="{{ $href }}" class="ks-stat" style="text-decoration:none;">
    @else
        <div class="ks-stat">
    @endif
        <div class="ks-stat-top">
            <p class="ks-stat-label">{{ $label }}</p>
            @if($icon)
                <div class="ks-stat-ico" style="background:linear-gradient(135deg, {{ $tint }}, {{ $tint }}cc);box-shadow:0 6px 14px {{ $soft }};">
                    <span style="display:flex;align-items:center;justify-content:center;">{!! $icon !!}</span>
                </div>
            @endif
        </div>
        <div>
            <p class="ks-stat-value">{{ $value }}</p>
            @if($trend)
                <p class="ks-stat-trend {{ str_starts_with($trend, '-') ? 'down' : 'up' }}">{{ $trend }}</p>
            @elseif($sub)
                <p class="ks-stat-sub">{{ $sub }}</p>
            @endif
        </div>
    @if($href)
        </a>
    @else
        </div>
    @endif
@endif