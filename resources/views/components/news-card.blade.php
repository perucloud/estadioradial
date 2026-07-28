@props(['post', 'featured' => false])

<article {{ $attributes->class(['news-card', 'news-card--featured' => $featured]) }}>
    <a class="news-card__image" href="{{ route('posts.show', [$post->category, $post]) }}">
        <img src="{{ $post->image }}" alt="" loading="{{ $featured ? 'eager' : 'lazy' }}">
    </a>
    <div class="news-card__body">
        <a
            class="category-pill"
            style="--category-color: {{ $post->category->color }}"
            href="{{ route('posts.category', $post->category) }}"
        >{{ $post->category->name }}</a>
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

