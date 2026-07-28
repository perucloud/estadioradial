@props(['program'])

<article class="program-card">
    <a href="{{ route('programs.show', $program) }}">
        <img src="{{ $program->image }}" alt="" loading="lazy">
        <div class="program-card__body">
            <h3>{{ $program->title }}</h3>
            <p>{{ $program->summary }}</p>
            <span>Conocer el programa →</span>
        </div>
    </a>
</article>

