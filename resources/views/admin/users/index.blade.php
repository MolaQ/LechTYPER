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
@include('admin.partials.quick-nav')
<div class="admin-shell">
    <div class="container-fluid"><div class="row min-vh-100">
        <aside class="admin-sidebar col-lg-2 p-4 d-flex flex-column"><a class="brand d-flex align-items-center gap-2 mb-5" href="{{ route('home') }}"><span class="brand-mark">LP</span><span>#LechTYPER</span></a><div class="sidebar-label mb-2">Panel zarządzania</div><nav class="nav flex-column gap-1"><a class="nav-link px-3 py-2" href="{{ route('admin.dashboard') }}">▦ <span class="ms-2">Dashboard</span></a><a class="nav-link active px-3 py-2" href="{{ route('admin.users.index') }}">♧ <span class="ms-2">Użytkownicy</span></a><a class="nav-link px-3 py-2" href="#mecze">◷ <span class="ms-2">Mecze i typy</span></a><a class="nav-link px-3 py-2" href="#wpisy">▤ <span class="ms-2">Moderacja wpisów</span></a></nav><div class="mt-auto pt-5"><a class="nav-link px-3 py-2" href="{{ route('home') }}">← <span class="ms-2">Wróć do witryny</span></a></div></aside>
        <main class="admin-content col-lg-10 p-3 p-md-4 p-xl-5"><header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"><div><p class="eyebrow mb-2">Zarządzanie dostępem</p><h1 class="font-display h3 mb-1">Użytkownicy</h1><p class="text-muted-custom mb-0">Filtruj listę w czasie rzeczywistym i zarządzaj dostępem Premium.</p></div><div class="d-flex gap-2"><button class="btn btn-primary" type="button" data-open-create>＋ Dodaj użytkownika</button><form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="btn btn-outline-secondary">Wyloguj</button></form></div></header><section class="bg-white border rounded-3 p-3 p-md-4"><livewire:admin.users-table /></section></main>
    </div></div>
</div>
@livewireScripts
</body>
</html>
