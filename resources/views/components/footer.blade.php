@props(['contactEmail', 'contact', 'identity', 'logoUrl' => null])

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="brand brand--footer" href="{{ route('home') }}">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $identity['name'] }}" style="max-width:180px;max-height:64px;object-fit:contain">
                @else
                <span class="brand__signal" aria-hidden="true"><i></i><i></i><i></i></span>
                <span class="brand__name">{{ $identity['name'] }}</span>
                @endif
            </a>
            <p>{{ $identity['slogan'] ?: 'Noticias, cultura, entretenimiento y radio en vivo.' }}</p>
        </div>
        <div>
            <h2>Explora</h2>
            <a href="{{ route('posts.index') }}">Últimas noticias</a>
            <a href="{{ route('programs.index') }}">Programas</a>
            <a href="{{ route('schedule') }}">Programación</a>
        </div>
        <div>
            <h2>En vivo</h2>
            <a href="{{ route('live') }}">Radio en vivo</a>
            <a href="{{ route('live') }}#video">Video en vivo</a>
            <span>Señal configurable</span>
        </div>
        <div>
            <h2>Contacto</h2>
            @if ($contact['address'])<span>{{ $contact['address'] }}</span>@endif
            @if ($contact['phone'])<a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a>@endif
            @if ($contact['whatsapp'])<a href="https://wa.me/{{ preg_replace('/\D/', '', $contact['whatsapp']) }}" target="_blank" rel="noopener">WhatsApp</a>@endif
            <x-utility-access-links :email="$contactEmail" surface="footer" />
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <span>&copy; {{ now()->year }} {{ $identity['name'] }}</span>
            <span>Primera versión en desarrollo</span>
        </div>
    </div>
</footer>
