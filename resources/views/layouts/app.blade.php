<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) ?: 'en' }}" dir="ltr" x-data="tallstackui_darkTheme({ dark: true })"
    x-bind:class="{
        'dark bg-gray-900': darkTheme,
        'bg-gray-100': !darkTheme
    }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Genealogy Application - Manage your family tree and discover your ancestry.">

    <title>{{ config('app.name', 'Genealogy') }} @yield('title')</title>

    <!-- favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/favicon/favicon-16x16.png') }}" sizes="16x16">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon/favicon-32x32.png') }}" sizes="32x32">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon/favicon-96x96.png') }}" sizes="96x96">

    <!-- fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanuman:wght@400;700&family=Koulen&display=swap" rel="stylesheet">

    <!-- scripts -->
    <tallstackui:script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- styles -->
    @livewireStyles
    @filamentStyles
    @stack('styles')
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen">
        <!-- notifications -->
        <x-ts-toast />

        <!-- offcanvas menu -->
        @include('layouts.partials.offcanvas')

        <!-- header -->
        @include('layouts.partials.header')

        <!-- main content -->
        <main>
            {{ $slot }}
        </main>

        <!-- footer -->
        @include('layouts.partials.footer')
    </div>

    <!-- scripts -->
    @livewireScripts
    @filamentScripts
    @stack('scripts')
</body>

</html>
