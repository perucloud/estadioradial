@props(['advertisement', 'position'])

@if ($advertisement instanceof \App\Models\Advertisement)
<aside class="ad-slot ad-slot--dark" aria-label="Publicidad {{ $position }}">
    @if ($advertisement->destination_url)<a href="{{ $advertisement->destination_url }}" @if($advertisement->open_in_new_tab) target="_blank" rel="noopener sponsored" @endif>@endif
    <span class="ad-slot__label">Publicidad</span>
    <img src="{{ $advertisement->imageUrl() }}" alt="{{ $advertisement->alt_text }}" loading="lazy">
    @if ($advertisement->destination_url)</a>@endif
</aside>
@else
<aside class="ad-slot ad-slot--{{ $advertisement['tone'] }}" aria-label="Publicidad {{ $position }}">
    <div class="ad-slot__art" aria-hidden="true">
        <span></span><span></span><span></span>
    </div>
    <div class="ad-slot__body">
        <span class="ad-slot__label">Publicidad</span>
        <small>{{ $advertisement['eyebrow'] }}</small>
        <h3>{{ $advertisement['title'] }}</h3>
        <p>{{ $advertisement['description'] }}</p>
        <span class="ad-slot__cta">Información comercial →</span>
    </div>
</aside>
@endif
