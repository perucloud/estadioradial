@if ($paginator->hasPages())
    <nav class="portal-pagination" role="navigation" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="portal-pagination__control is-disabled" aria-disabled="true">
                <span aria-hidden="true">←</span>
                <span>Anterior</span>
            </span>
        @else
            <a
                class="portal-pagination__control"
                href="{{ $paginator->previousPageUrl() }}"
                rel="prev"
                aria-label="Ir a la página anterior"
            >
                <span aria-hidden="true">←</span>
                <span>Anterior</span>
            </a>
        @endif

        <div class="portal-pagination__pages" aria-label="Páginas disponibles">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="portal-pagination__ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span
                                class="portal-pagination__page is-current"
                                aria-current="page"
                                aria-label="Página {{ $page }}, actual"
                            >{{ $page }}</span>
                        @else
                            <a
                                class="portal-pagination__page"
                                href="{{ $url }}"
                                aria-label="Ir a la página {{ $page }}"
                            >{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a
                class="portal-pagination__control"
                href="{{ $paginator->nextPageUrl() }}"
                rel="next"
                aria-label="Ir a la página siguiente"
            >
                <span>Siguiente</span>
                <span aria-hidden="true">→</span>
            </a>
        @else
            <span class="portal-pagination__control is-disabled" aria-disabled="true">
                <span>Siguiente</span>
                <span aria-hidden="true">→</span>
            </span>
        @endif
    </nav>
@endif
