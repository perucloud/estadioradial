@extends('layouts.app')

@section('title', $post->title.' | Estación Radial')
@section('description', $post->excerpt)

@section('content')
    <article class="article">
        <header class="article__header">
            <div class="container container--article">
                <a
                    class="category-pill"
                    style="--category-color: {{ $post->category->color }}"
                    href="{{ route('posts.category', $post->category) }}"
                >{{ $post->category->name }}</a>
                <h1>{{ $post->title }}</h1>
                <p class="article__lead">{{ $post->excerpt }}</p>
                <div class="article__meta">
                    <span>Por {{ $post->author }}</span>
                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                        {{ $post->published_at->translatedFormat('d \d\e F \d\e Y, H:i') }}
                    </time>
                </div>
            </div>
        </header>

        <div class="container container--article">
            <img class="article__cover" src="{{ $post->image }}" alt="">
            <div class="article__body">{!! $post->body !!}</div>
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

