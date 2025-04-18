<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', '') }}@yield('title', '')</title>
    <meta name="author" content="zhoulin" />
    <meta name="email" content="zhoulin@xiangrong.pro" />
    <meta name="web" content="https://xiangrong.pro" />

    <link rel="icon" href="{{ asset('favicon.ico') }}" mce_href="{{ asset('favicon.ico') }}" type="image/x-icon" />

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @yield('style')
</head>

<body>
    <div id="app">
        <main>
            @include('layouts.header')
            @yield('content')
        </main>
        @include('layouts.footer')
    </div>
    @yield('script')
</body>

</html>
