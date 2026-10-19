<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'AirTracking')</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/airtracking.css') }}">
<link rel="stylesheet" href="{{ asset('css/header.css') }}">
@stack('head')
</head>
<body>
@include('partials.site-header')
<div class="wrap">
@yield('content')
</div>
@stack('scripts')
</body>
</html>
