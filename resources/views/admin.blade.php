<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Panel admina</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="admin-shell">
    <div class="container-fluid">
        <div class="row min-vh-100">
            @include('admin.partials.sidebar')

            <main class="admin-content col-lg-10 p-3 p-md-4 p-xl-5">
                <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <p class="eyebrow mb-2">Centrum dowodzenia</p>
                        <h1 class="font-display h3 mb-1">Dzień dobry, {{ auth()->user()->name }}.</h1>
                        <p class="text-muted-custom mb-0">Zarządzaj społecznością i przygotuj kolejną kolejkę.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-primary" href="{{ route('admin.users.index') }}">Użytkownicy</a>
                        <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="btn btn-outline-secondary">Wyloguj</button></form>
                    </div>
                </header>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-3"><div class="stat-card bg-white p-3"><p class="text-muted-custom small mb-2">Aktywni kibice</p><span class="stat-value">1 284</span></div></div>
                    <div class="col-sm-6 col-xl-3"><div class="stat-card bg-white p-3"><p class="text-muted-custom small mb-2">Oczekujące wpisy</p><span class="stat-value">24</span></div></div>
                    <div class="col-sm-6 col-xl-3"><div class="stat-card bg-white p-3"><p class="text-muted-custom small mb-2">Typy w tej kolejce</p><span class="stat-value">3 842</span></div></div>
                    <div class="col-sm-6 col-xl-3"><div class="stat-card bg-white p-3"><p class="text-muted-custom small mb-2">Mecze aktywne</p><span class="stat-value">8</span></div></div>
                </div>

                <section class="bg-white border rounded-3 p-4">
                    <p class="eyebrow mb-2">Następny krok</p>
                    <h2 class="font-display h5">Zarządzaj użytkownikami</h2>
                    <p class="text-muted-custom mb-3">Lista użytkowników ma filtry działające w czasie rzeczywistym. Premium można nadać dopiero po pierwszym logowaniu użytkownika.</p>
                    <a class="btn btn-outline-primary" href="{{ route('admin.users.index') }}">Otwórz listę użytkowników →</a>
                </section>
            </main>
        </div>
    </div>
</div>
</body>
</html>
