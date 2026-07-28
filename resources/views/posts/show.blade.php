@extends('layouts.app')

@section('title', $post->title.' | Estación Radial')
@section('description', $post->excerpt)

@section('content')
    <article class="article">
        <div class="container article-layout">
            <div class="article-content">
                @if ($post->coverUrl())
                    <img class="article__cover" src="{{ $post->coverUrl() }}" alt="{{ $post->media?->alt_text ?? '' }}">
                @endif
                @if ($post->image_credit || $post->image_license)
                    <p class="article__image-credit">
                        {{ collect([$post->image_credit, $post->image_license])->filter()->join(' · ') }}
                    </p>
                @endif
                <header class="article__header">
                    <div class="article__kicker">
                        <a
                            class="category-pill"
                            style="--category-color: {{ $post->category->color }}"
                            href="{{ route('posts.category', $post->category) }}"
                        >{{ $post->category->name }}</a>
                        <time datetime="{{ $post->published_at->toIso8601String() }}">
                            {{ $post->published_at->translatedFormat('d \d\e F \d\e Y, H:i') }}
                        </time>
                    </div>
                    <h1>{{ $post->title }}</h1>
                    <p class="article__lead">{{ $post->excerpt }}</p>
                    <div class="article__meta">
                        <span>Por {{ $post->author }}</span>
                        <span>{{ number_format($post->views_count) }} lecturas</span>
                    </div>
                    @if ($post->tags->isNotEmpty())
                        <div class="article__tags" aria-label="Temas relacionados">
                            @foreach ($post->tags as $tag)
                                <span>#{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </header>
                <div class="article__body">{!! $post->body !!}</div>
                @if ($post->source_name && $post->source_url)
                    <aside class="article__source">
                        <span>Fuente consultada</span>
                        <a href="{{ $post->source_url }}" target="_blank" rel="noopener noreferrer nofollow">
                            {{ $post->source_name }} ↗
                        </a>
                        <p>Estación Radial elaboró un resumen editorial propio a partir de la información enlazada.</p>
                    </aside>
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
    </article>

    @if ($relatedPosts->isNotEmpty())
        <section class="section section--soft">
            <div class="container">
                <div class="section-heading">
                    <h2>También puede interesarte</h2>
                </div>
                <div class="news-grid">
                    @foreach ($relatedPosts as $relatedPost)
                        <x-news-card :post="$relatedPost" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
