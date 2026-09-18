<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Użytkownicy</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
<div class="admin-shell">
    <div class="container-fluid"><div class="row min-vh-100">
        @include('admin.partials.sidebar')
        <main class="admin-content col-lg-10 p-3 p-md-4 p-xl-5"><header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"><div><p class="eyebrow mb-2">Zarządzanie dostępem</p><h1 class="font-display h3 mb-1">Użytkownicy</h1><p class="text-muted-custom mb-0">Filtruj listę w czasie rzeczywistym i zarządzaj dostępem Premium.</p></div><div class="d-flex gap-2"><button class="btn btn-primary" type="button" data-open-create>＋ Dodaj użytkownika</button><form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="btn btn-outline-secondary">Wyloguj</button></form></div></header><section class="bg-white border rounded-3 p-3 p-md-4"><livewire:admin.users-table /></section></main>
    </div></div>
</div>
@livewireScripts
</body>
</html>
