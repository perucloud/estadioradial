@props(['title', 'eyebrow' => null])

<section class="page-hero">
    <div class="container">
        @if ($eyebrow)<span class="eyebrow">{{ $eyebrow }}</span>@endif
        <h1>{{ $title }}</h1>
        @if ($slot->isNotEmpty())<p>{{ $slot }}</p>@endif
    </div>
</section>
