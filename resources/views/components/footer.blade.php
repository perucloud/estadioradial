<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="brand brand--footer" href="{{ route('home') }}">
                <span class="brand__signal" aria-hidden="true"><i></i><i></i><i></i></span>
                <span class="brand__name">estación<br><strong>radial</strong></span>
            </a>
            <p>Noticias, cultura, entretenimiento y la señal de radio que te acompaña todos los días.</p>
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
            <span>Ciudad, Perú</span>
            <a href="mailto:contacto@estacionradial.test">contacto@estacionradial.test</a>
            <span>Redes sociales próximamente</span>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <span>&copy; {{ now()->year }} Estación Radial</span>
            <span>Primera versión en desarrollo</span>
        </div>
    </div>
</footer>

