@extends('layouts.app')

@section('title', $title.' | Estación Radial')
@section('description', $category->description ?? 'Noticias y actualidad en Estación Radial.')

@section('content')
    <x-page-hero :title="$title" eyebrow="Noticias">
        {{ $category->description ?? 'Información clara y oportuna desde nuestra mesa de noticias.' }}
    </x-page-hero>

    <section class="section">
        <div class="container section-sidebar-layout" data-sidebar-layout>
            <div class="section-sidebar-main" data-sidebar-main>
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
