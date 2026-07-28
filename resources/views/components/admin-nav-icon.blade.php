@props(['name'])

<img
    {{ $attributes->class('admin-nav-icon') }}
    src="{{ asset("images/admin/icons/{$name}") }}"
    alt=""
    aria-hidden="true"
>
