@props(['post', 'settings' => []])

@php
    $lineage = $post->location?->lineage() ?? collect();
    $province = $lineage->firstWhere('type', 'province');
    $district = $lineage->firstWhere('type', 'district');
    $showProvince = (bool) ($settings['highlight_province'] ?? false) && $province;
    $showDistrict = (bool) ($settings['highlight_district'] ?? false) && $district;
@endphp

@if ($showProvince || $showDistrict)
    <span class="regional-location-badges" aria-label="Ubicación destacada">
        @if ($showProvince)
            <a
                class="regional-location-badge regional-location-badge--province"
                href="{{ $province->publicUrl() }}"
                title="Provincia de {{ $province->name }}"
            >
                <span aria-hidden="true">P</span>
                {{ $province->name }}
            </a>
        @endif
        @if ($showDistrict)
            <a
                class="regional-location-badge regional-location-badge--district"
                href="{{ $district->publicUrl() }}"
                title="Distrito de {{ $district->name }}"
            >
                <span aria-hidden="true">D</span>
                {{ $district->name }}
            </a>
        @endif
    </span>
@endif
