@props(['post', 'featured' => false])

<article {{ $attributes->class(['news-card', 'news-card--featured' => $featured]) }}>
    <a class="news-card__image" href="{{ route('posts.show', [$post->category, $post]) }}">
        <img src="{{ $post->coverUrl('card') }}" alt="{{ $post->media?->alt_text ?? '' }}" loading="{{ $featured ? 'eager' : 'lazy' }}">
    </a>
    <div class="news-card__body">
        <div class="story-labels">
            <a
                class="category-pill"
                style="--category-color: {{ $post->category->color }}"
                href="{{ route('posts.category', $post->category) }}"
            >{{ $post->category->name }}</a>
            <x-editorial-territory-badge variant="compact" />
        </div>
        @if ($post->location)
            <a class="location-link" href="{{ $post->location->publicUrl() }}">
                <span aria-hidden="true">⌖</span> {{ $post->location->name }}
            </a>
        @endif
        <h3>
            <a href="{{ route('posts.show', [$post->category, $post]) }}">{{ $post->title }}</a>
        </h3>
        @if ($featured)
            <p>{{ $post->excerpt }}</p>
        @endif
        <time datetime="{{ $post->published_at->toIso8601String() }}">
            {{ $post->published_at->diffForHumans() }}
        </time>
    </div>
</article>
