@props(['advertisement', 'position'])

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

