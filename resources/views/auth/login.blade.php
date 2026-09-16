<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Logowanie</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="container py-5"><div class="row justify-content-center"><div class="col-12 col-md-7 col-lg-5"><a class="brand d-flex align-items-center gap-2 mb-5" href="{{ route('home') }}"><span class="brand-mark">LP</span><span>#LechTYPER</span></a><section class="bg-white border rounded-3 p-4 p-md-5 shadow-sm"><p class="eyebrow mb-2">Strefa kibiców</p><h1 class="font-display h3 mb-2">Zaloguj się</h1><p class="text-muted-custom mb-4">Dane dostępu otrzymasz od administratora.</p>@if ($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif<form method="POST" action="{{ route('login.submit') }}">@csrf<div class="mb-3"><label class="form-label small" for="email">Email</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus></div><div class="mb-3"><label class="form-label small" for="password">Hasło</label><input class="form-control" id="password" name="password" type="password" required></div><button class="btn btn-primary w-100">Zaloguj się</button></form><a class="d-block text-center small mt-3" href="{{ route('password.request') }}">Przypomnij hasło</a></section></div></div></main>
</body>
</html>