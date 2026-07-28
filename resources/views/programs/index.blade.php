@extends('layouts.app')

@section('title', 'Programas | Estación Radial')

@section('content')
    <x-page-hero title="Programas" eyebrow="Nuestra señal">
        Información, cultura, música y entretenimiento para acompañarte cada día.
    </x-page-hero>

    <section class="section">
        <div class="container section-sidebar-layout" data-sidebar-layout>
            <div class="section-sidebar-main" data-sidebar-main>
                <div class="program-grid">
                    @foreach ($programs as $program)
                        <x-program-card :program="$program" />
                    @endforeach
                </div>
                <div class="pagination-wrap">{{ $programs->links() }}</div>
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
