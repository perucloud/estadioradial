@props([
    'settings',
    'mostRead',
    'latest',
    'advertisements',
    'categories',
    'socialLinks',
])

@php
    $allowedModules = ['most_read', 'latest', 'advertisements', 'social', 'categories'];
    $configuredModules = is_array($settings['modules'] ?? null) ? $settings['modules'] : $allowedModules;
    $modules = array_values(array_intersect($configuredModules, $allowedModules));
@endphp

<aside
    class="article-sidebar {{ ($settings['sticky'] ?? true) ? 'article-sidebar--sticky' : '' }}"
    aria-label="Información complementaria"
>
    @foreach ($modules as $module)
        @switch($module)
            @case('most_read')
                @if ($mostRead->isNotEmpty())
                    <section class="sidebar-panel" aria-labelledby="sidebar-most-read">
                        <div class="sidebar-panel__heading">
                            <span>Tendencias</span>
                            <h2 id="sidebar-most-read">Las más leídas</h2>
                        </div>
                        <ol class="sidebar-news sidebar-news--ranked">
                            @foreach ($mostRead as $item)
                                <li>
                                    <a class="sidebar-news__image" href="{{ route('posts.show', [$item->category, $item]) }}">
                                        <img src="{{ $item->image }}" alt="" loading="lazy">
                                        <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    </a>
                                    <div>
                                        <a
                                            class="sidebar-news__category"
                                            style="--category-color: {{ $item->category->color }}"
                                            href="{{ route('posts.category', $item->category) }}"
                                        >{{ $item->category->name }}</a>
                                        <h3>
                                            <a href="{{ route('posts.show', [$item->category, $item]) }}">
                                                {{ $item->title }}
                                            </a>
                                        </h3>
                                        <small>{{ number_format($item->views_count) }} lecturas</small>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
                @break

            @case('latest')
                @if ($latest->isNotEmpty())
                    <section class="sidebar-panel" aria-labelledby="sidebar-latest">
                        <div class="sidebar-panel__heading">
                            <span>Al momento</span>
                            <h2 id="sidebar-latest">Últimas noticias</h2>
                        </div>
                        <div class="sidebar-latest">
                            @foreach ($latest as $item)
                                <article>
                                    <a href="{{ route('posts.show', [$item->category, $item]) }}">
                                        <span style="--category-color: {{ $item->category->color }}">{{ $item->category->name }}</span>
                                        <h3>{{ $item->title }}</h3>
                                        <time datetime="{{ $item->published_at->toIso8601String() }}">
                                            {{ $item->published_at->diffForHumans() }}
                                        </time>
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif
                @break

            @case('advertisements')
                @if ($advertisements->isNotEmpty())
                    <section class="sidebar-ads" aria-label="Publicidad">
                        @foreach ($advertisements as $advertisement)
                            @if ($advertisement->destination_url)
                                <a
                                    class="sidebar-ad"
                                    href="{{ $advertisement->destination_url }}"
                                    @if ($advertisement->open_in_new_tab) target="_blank" rel="noopener sponsored" @endif
                                >
                                    <span>Publicidad</span>
                                    <img src="{{ $advertisement->image }}" alt="{{ $advertisement->alt_text }}" loading="lazy">
                                </a>
                            @else
                                <div class="sidebar-ad">
                                    <span>Publicidad</span>
                                    <img src="{{ $advertisement->image }}" alt="{{ $advertisement->alt_text }}" loading="lazy">
                                </div>
                            @endif
                        @endforeach
                    </section>
                @endif
                @break

            @case('social')
                <section class="sidebar-panel sidebar-social" aria-labelledby="sidebar-social-title">
                    <div class="sidebar-panel__heading">
                        <span>Comunidad</span>
                        <h2 id="sidebar-social-title">Síguenos</h2>
                    </div>
                    <div class="sidebar-social__links">
                        @foreach ([
                            'facebook' => ['Facebook', 'f'],
                            'x' => ['X', 'X'],
                            'tiktok' => ['TikTok', '♪'],
                            'instagram' => ['Instagram', '◎'],
                            'youtube' => ['YouTube', '▶'],
                        ] as $network => [$label, $icon])
                            @if (! empty($socialLinks[$network]))
                                <a href="{{ $socialLinks[$network] }}" target="_blank" rel="noopener noreferrer">
                                    <span aria-hidden="true">{{ $icon }}</span>
                                    {{ $label }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </section>
                @break

            @case('categories')
                @if ($categories->isNotEmpty())
                    <section class="sidebar-panel" aria-labelledby="sidebar-categories">
                        <div class="sidebar-panel__heading">
                            <span>Explorar</span>
                            <h2 id="sidebar-categories">Categorías</h2>
                        </div>
                        <nav class="sidebar-categories" aria-label="Categorías de noticias">
                            @foreach ($categories as $category)
                                <a href="{{ route('posts.category', $category) }}">
                                    <span>
                                        <i style="--category-color: {{ $category->color }}"></i>
                                        {{ $category->name }}
                                    </span>
                                    <small>{{ $category->posts_count }}</small>
                                </a>
                            @endforeach
                        </nav>
                    </section>
                @endif
                @break
        @endswitch
    @endforeach
</aside>
