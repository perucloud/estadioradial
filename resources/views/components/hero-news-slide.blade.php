@props(['leadPost', 'secondaryPosts', 'isFirst' => false, 'position'])

<div
    id="hero-news-slide-{{ $position }}"
    class="hero-news-slide {{ $isFirst ? 'is-active' : '' }}"
    data-hero-slide
    aria-hidden="{{ $isFirst ? 'false' : 'true' }}"
    @unless ($isFirst) inert @endunless
>
    <div class="hero-grid">
        <article class="lead-story">
            <a class="lead-story__image" href="{{ route('posts.show', [$leadPost->category, $leadPost]) }}">
                <img src="{{ $leadPost->coverUrl('article') }}" alt="{{ $leadPost->media?->alt_text ?? '' }}">
                <span
                    class="category-pill"
                    style="--category-color: {{ $leadPost->category->color }}"
                >{{ $leadPost->category->name }}</span>
            </a>
            <div class="lead-story__body">
                @if ($isFirst)
                    <h1>
                        <a href="{{ route('posts.show', [$leadPost->category, $leadPost]) }}">
                            {{ $leadPost->title }}
                        </a>
                    </h1>
                @else
                    <h2 class="lead-story__title">
                        <a href="{{ route('posts.show', [$leadPost->category, $leadPost]) }}">
                            {{ $leadPost->title }}
                        </a>
                    </h2>
                @endif
                <p>{{ $leadPost->excerpt }}</p>
                <time datetime="{{ $leadPost->published_at->toIso8601String() }}">
                    {{ $leadPost->published_at->format('H:i') }} HS.
                </time>
            </div>
        </article>

        <div class="hero-grid__side">
            @foreach ($secondaryPosts as $post)
                <article class="hero-story {{ $loop->even ? 'hero-story--reversed' : '' }}">
                    <a class="hero-story__image" href="{{ route('posts.show', [$post->category, $post]) }}">
                        <img src="{{ $post->coverUrl('card') }}" alt="{{ $post->media?->alt_text ?? '' }}">
                    </a>
                    <div class="hero-story__body">
                        <a
                            class="category-pill"
                            style="--category-color: {{ $post->category->color }}"
                            href="{{ route('posts.category', $post->category) }}"
                        >{{ $post->category->name }}</a>
                        <h2>
                            <a href="{{ route('posts.show', [$post->category, $post]) }}">
                                {{ $post->title }}
                            </a>
                        </h2>
                        <time datetime="{{ $post->published_at->toIso8601String() }}">
                            {{ $post->published_at->format('H:i') }} HS.
                        </time>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</div>
