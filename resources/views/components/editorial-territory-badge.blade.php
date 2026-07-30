@props(['post', 'variant' => 'inline'])

@php
    $settings = app(\App\Support\DefaultLocationSettings::class);
    $identity = $settings->editorialIdentity();
@endphp

@if ($settings->shouldShowEditorialBadge($post))
    <span
        {{ $attributes->class([
            'territory-badge',
            'territory-badge--overlay' => $variant === 'overlay',
            'territory-badge--compact' => $variant === 'compact',
        ]) }}
        aria-label="Sede editorial: {{ $identity['label'] }}"
        title="Sede editorial"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 21s6-5.45 6-11A6 6 0 0 0 6 10c0 5.55 6 11 6 11Z"/>
            <circle cx="12" cy="10" r="2.25"/>
        </svg>
        <span>{{ $identity['label'] }}</span>
    </span>
@endif
