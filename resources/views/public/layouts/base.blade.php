<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="@yield('meta_description', 'ASRI Boarding House — Hunian kost asri 32 kamar di Tembalang, Semarang.')">

        <title>@yield('title', config('app.name', 'ASRI Boarding House'))</title>

        @vite(['resources/css/public.css', 'resources/js/public.js'])
    </head>
    <body class="min-h-screen bg-cream-50 font-sans text-ink-900 antialiased">
        @yield('content')
    </body>
</html>

