@props(['title' => null])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow">

<title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

<link rel="icon" href="{{ asset(config('vestiario.favicon_path')) }}">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
