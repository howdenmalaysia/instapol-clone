<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, minimum-scale=1">
        <meta name="author" content="David Choy">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="access-token" content="{{ session('access_token') }}">
        <meta name="theme-color" content="#9A5CD0">

        <meta property="og:title" content="@yield('title', config('app.name') . ' : Insurance in an instant')">
        <meta property="og:type" content="article">
        <meta property="og:description" content="@yield('meta_description', 'Your policy, in an instant. instaPol is the quick, hassle-free way to buy insurance.')">
        <meta property="og:image" content="{{ asset('images/instapol_logo.png') }}">
        <meta property="og:url" content="{{ url()->current() }}">
        
        <title>@yield('title', config('app.name'))</title>

        <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}" type="image/png">
        <link rel="stylesheet" href="{{ mix('css/frontend.css') }}">
    </head>
    <body class="d-flex flex-column min-vh-100">
        @include('frontend.includes.navbar')
        
        <div class="content">
            @yield('content')
        </div>

        @include('frontend.includes.footer')

        {{-- JS --}}
        @stack('before-scripts')
        <script src="{{ mix('js/manifest.js') }}"></script>
        <script src="{{ mix('js/vendor.js') }}"></script>
        <script src="{{ mix('js/app.js') }}"></script>
        @stack('after-scripts')

        @include('frontend.includes.mb')
    </body>
</html>