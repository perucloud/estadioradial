@if ($paginator->hasPages())
    <nav class="portal-pagination" role="navigation" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="portal-pagination__control is-disabled" aria-disabled="true">
                <span aria-hidden="true">←</span>
                <span>Anterior</span>
            </span>
        @else
            <a class="portal-pagination__control" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                <span aria-hidden="true">←</span>
                <span>Anterior</span>
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a class="portal-pagination__control" href="{{ $paginator->nextPageUrl() }}" rel="next">
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
