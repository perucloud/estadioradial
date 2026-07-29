@extends('layouts.app')

@section('title', ($location->seo_title ?? $title).' | Estación Radial')
@section('description', $location->seo_description ?? $category->description ?? $description ?? 'Noticias y actualidad en Estación Radial.')

@section('content')
    <x-page-hero :title="$title" eyebrow="Noticias">
        {{ $description ?? $category->description ?? 'Información clara y oportuna desde nuestra mesa de noticias.' }}
    </x-page-hero>

    <section class="section">
        <div class="container section-sidebar-layout" data-sidebar-layout>
            <div class="section-sidebar-main" data-sidebar-main>
                @if (isset($regionalIndex) || isset($location))
                    <nav class="territory-breadcrumbs" aria-label="Ruta territorial">
                        <a href="{{ route('home') }}">Inicio</a>
                        <span aria-hidden="true">/</span>
                        <a href="{{ route('posts.locations.index') }}">Noticias regionales</a>
                        @foreach ($locationTrail ?? collect() as $trailLocation)
                            <span aria-hidden="true">/</span>
                            @if (! $loop->last)
                                <a href="{{ $trailLocation->publicUrl() }}">{{ $trailLocation->name }}</a>
                            @else
                                <strong aria-current="page">{{ $trailLocation->name }}</strong>
                            @endif
                        @endforeach
                    </nav>

                    @if (($territories ?? collect())->isNotEmpty())
                        <section class="territory-browser" aria-labelledby="territory-browser-title">
                            <div>
                                <span class="eyebrow">{{ isset($location) ? 'Explorar el territorio' : 'Cobertura territorial' }}</span>
                                <h2 id="territory-browser-title">
                                    {{ isset($location) ? 'Divisiones de '.$location->name : 'Regiones disponibles' }}
                                </h2>
                            </div>
                            <div class="territory-links">
                                @foreach ($territories as $territory)
                                    <a href="{{ $territory->publicUrl() }}">
                                        <span>{{ $territory->typeLabel() }}</span>
                                        <strong>{{ $territory->name }}</strong>
                                        <b aria-hidden="true">→</b>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endif

                @if ($posts->isEmpty())
                    <div class="empty-state">
                        <h2>Aún no hay publicaciones</h2>
                        <p>Estamos preparando nuevo contenido para esta sección.</p>
                    </div>
                @else
                    <div class="news-grid">
                        @foreach ($posts as $post)
                            <x-news-card :post="$post" />
                        @endforeach
                    </div>
                    <div class="pagination-wrap">{{ $posts->links() }}</div>
                @endif
            </div>

            <x-article-sidebar
                :settings="$sidebarSettings"
                :most-read="$sidebarMostRead"
                :latest="$sidebarLatest"
                :advertisements="$sidebarAdvertisements"
                :categories="$sidebarCategories"
                :social-links="$sidebarSocialLinks"
            />
        </div>
    </section>
@endsection
