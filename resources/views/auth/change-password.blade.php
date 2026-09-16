<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Ustaw hasło</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="container py-5"><div class="row justify-content-center"><div class="col-12 col-md-7 col-lg-5"><a class="brand d-flex align-items-center gap-2 mb-5" href="{{ route('home') }}"><span class="brand-mark">LP</span><span>#LechTYPER</span></a><section class="bg-white border rounded-3 p-4 p-md-5 shadow-sm"><p class="eyebrow mb-2">Pierwsze logowanie</p><h1 class="font-display h3 mb-2">Ustaw własne hasło</h1><p class="text-muted-custom mb-4">Hasło tymczasowe jest ważne tylko do momentu jego zmiany.</p>@if ($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif<form method="POST" action="{{ route('password.update') }}">@csrf<div class="mb-3"><label class="form-label small" for="password">Nowe hasło</label><input class="form-control" id="password" name="password" type="password" minlength="12" required autofocus><div class="form-text">Minimum 12 znaków.</div></div><div class="mb-4"><label class="form-label small" for="password_confirmation">Powtórz nowe hasło</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="12" required></div><button class="btn btn-primary w-100">Zapisz nowe hasło</button></form></section></div></div></main>
</body>
</html>
