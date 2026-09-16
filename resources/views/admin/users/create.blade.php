<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Dodaj użytkownika</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="container py-5"><a class="brand d-flex align-items-center gap-2 mb-5" href="{{ route('admin.users.index') }}"><span class="brand-mark">LP</span><span>#LechTYPER</span></a><div class="row justify-content-center"><div class="col-12 col-xl-8"><div class="d-flex justify-content-between align-items-end mb-4"><div><p class="eyebrow mb-2">Panel admina</p><h1 class="font-display h3 mb-2">Dodaj użytkownika</h1><p class="text-muted-custom mb-0">System wygeneruje hasło tymczasowe i wyśle je na email.</p></div><a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Wróć do listy</a></div><section class="bg-white border rounded-3 p-4 p-md-5">@if ($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif<form method="POST" action="{{ route('admin.users.store') }}">@csrf<div class="row g-3"><div class="col-md-6"><label class="form-label" for="name">Imię i nazwisko</label><input class="form-control" id="name" name="name" value="{{ old('name') }}" required></div><div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required></div><div class="col-md-6"><label class="form-label" for="x-username">Nazwa konta X</label><input class="form-control" id="x-username" name="x_username" value="{{ old('x_username') }}" placeholder="np. LechPoznanSA" required></div><div class="col-md-6"><label class="form-label" for="role">Rola</label><select class="form-select" id="role" name="role" required><option value="kibol">Kibol</option><option value="moderator">Moderator</option>@if (auth()->user()->role === 'superadmin')<option value="admin">Admin</option><option value="superadmin">Superadmin</option>@endif</select></div></div><hr class="my-4"><p class="text-muted-custom small mb-0">Nazwa konta X jest zarządzana wyłącznie przez admina.</p><button class="btn btn-primary mt-4">Utwórz konto</button></form></section></div></div></main>
</body>
</html>
