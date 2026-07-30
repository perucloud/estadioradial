@extends('layouts.app')

@section('title', 'Vista previa: '.$post->title)
@section('description', $post->excerpt)

@section('content')
    <div class="preview-banner" role="status">
        Vista previa administrativa · Esta versión no es pública
        <a href="{{ route('admin.posts.edit', $post) }}">Volver a editar</a>
    </div>
    <article class="article">
        <div class="container container--article">
            @if ($post->coverUrl())
                <img class="article__cover" src="{{ $post->coverUrl() }}" alt="{{ $post->media?->alt_text ?? '' }}">
            @endif
            <header class="article__header">
                <div class="article__kicker">
                    <span class="category-pill" style="--category-color: {{ $post->category->color }}">{{ $post->category->name }}</span>
                    <x-editorial-territory-badge :post="$post" />
                    <span>{{ ucfirst(str_replace('_', ' ', $post->status)) }}</span>
                </div>
                <h1>{{ $post->title }}</h1>
                <p class="article__lead">{{ $post->excerpt }}</p>
                <div class="article__meta"><span>Por {{ $post->author }}</span></div>
            </header>
            <div class="article__body">{!! $post->body !!}</div>
        </div>
    </article>
@endsection
