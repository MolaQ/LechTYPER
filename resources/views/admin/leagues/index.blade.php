<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Ligi</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@include('admin.partials.right-sidebar')
<div class="admin-shell">
    <div class="container-fluid"><div class="row min-vh-100">
        <aside class="admin-sidebar col-lg-2 p-4 d-flex flex-column">
            <a class="brand d-flex align-items-center gap-2 mb-5" href="{{ route('home') }}"><span class="brand-mark">LP</span><span>#LechTYPER</span></a>
            <div class="sidebar-label mb-2">Panel zarządzania</div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link px-3 py-2" href="{{ route('admin.dashboard') }}">▦ <span class="ms-2">Dashboard</span></a>
                <a class="nav-link px-3 py-2" href="{{ route('admin.users.index') }}">♧ <span class="ms-2">Użytkownicy</span></a>
                <a class="nav-link active px-3 py-2" href="{{ route('admin.leagues.index') }}">⚽ <span class="ms-2">Ligi</span></a>
                <a class="nav-link px-3 py-2" href="#mecze">◷ <span class="ms-2">Mecze i typy</span></a>
            </nav>
            <div class="mt-auto pt-5"><a class="nav-link px-3 py-2" href="{{ route('home') }}">← <span class="ms-2">Wróć do witryny</span></a></div>
        </aside>

        <main class="admin-content col-lg-10 p-3 p-md-4 p-xl-5">
            <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div><p class="eyebrow mb-2">Rozgrywki</p><h1 class="font-display h3 mb-1">Zarządzanie ligami</h1><p class="text-muted-custom mb-0">Twórz katalog lig i przypisuj je do konkretnych sezonów.</p></div>
                <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="btn btn-outline-secondary">Wyloguj</button></form>
            </header>

            @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <div class="row g-4">
                <section class="col-xl-7">
                    <div class="bg-white border rounded-3 p-3 p-md-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><p class="eyebrow mb-2">Katalog</p><h2 class="font-display h5 mb-0">Ligi</h2></div><span class="badge text-bg-light">{{ $leagues->count() }}</span></div>
                        <div class="d-grid gap-3">
                            @foreach($leagues as $league)
                                <form method="POST" action="{{ route('admin.leagues.update', $league) }}" class="border rounded-3 p-3">
                                    @csrf @method('PUT')
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5"><label class="form-label small">Nazwa</label><input class="form-control" name="name" value="{{ $league->name }}" required></div>
                                        <div class="col-md-4"><label class="form-label small">Slug</label><input class="form-control" name="slug" value="{{ $league->slug }}" required></div>
                                        <div class="col-md-3"><label class="form-label small">Poziom</label><input class="form-control" type="number" min="1" max="255" name="level" value="{{ $league->level }}" required></div>
                                        <div class="col-md-7"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_swiss" value="1" @checked($league->is_swiss)><span class="form-check-label">System szwajcarski</span></label><small class="text-muted-custom">Przypisania do sezonów: {{ $league->season_leagues_count }}</small></div>
                                        <div class="col-md-5 d-flex justify-content-md-end gap-2"><button class="btn btn-primary btn-sm">Zapisz</button></div>
                                    </div>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-white border rounded-3 p-3 p-md-4">
                        <p class="eyebrow mb-2">Nowa pozycja</p><h2 class="font-display h5 mb-3">Dodaj ligę</h2>
                        <form method="POST" action="{{ route('admin.leagues.store') }}"><div class="row g-3">@csrf
                            <div class="col-md-6"><label class="form-label">Nazwa</label><input class="form-control" name="name" placeholder="np. Ekstraklasa Kobiet" required></div>
                            <div class="col-md-6"><label class="form-label">Slug</label><input class="form-control" name="slug" placeholder="ekstraklasa-kobiet" required></div>
                            <div class="col-md-4"><label class="form-label">Poziom</label><input class="form-control" type="number" min="1" max="255" name="level" required></div>
                            <div class="col-md-8 d-flex align-items-end"><label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_swiss" value="1"><span class="form-check-label">System szwajcarski</span></label></div>
                            <div class="col-12"><button class="btn btn-primary">Dodaj ligę</button></div>
                        </div></form>
                    </div>
                </section>

                <section class="col-xl-5">
                    <div class="bg-white border rounded-3 p-3 p-md-4 mb-4">
                        <p class="eyebrow mb-2">Konfiguracja sezonu</p><h2 class="font-display h5 mb-3">Przypisz ligę do sezonu</h2>
                        <form method="POST" action="{{ route('admin.leagues.seasons.store') }}"><div class="row g-3">@csrf
                            <div class="col-12"><label class="form-label">Sezon</label><select class="form-select" name="season_id" required>@foreach($seasons as $season)<option value="{{ $season->id }}">{{ $season->name }} ({{ $season->status }})</option>@endforeach</select></div>
                            <div class="col-12"><label class="form-label">Liga</label><select class="form-select" name="league_id" required>@foreach($leagues as $league)<option value="{{ $league->id }}">{{ $league->name }} · poziom {{ $league->level }}</option>@endforeach</select></div>
                            <div class="col-6"><label class="form-label">Miejsca awansu</label><input class="form-control" type="number" min="0" name="promotion_places" value="0" required></div>
                            <div class="col-6"><label class="form-label">Miejsca spadku</label><input class="form-control" type="number" min="0" name="relegation_places" value="0" required></div>
                            <div class="col-12"><button class="btn btn-primary">Przypisz do sezonu</button></div>
                        </div></form>
                    </div>

                    @foreach($seasons as $season)
                        <div class="bg-white border rounded-3 p-3 p-md-4 mb-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><p class="eyebrow mb-2">{{ $season->status }}</p><h2 class="font-display h5 mb-0">{{ $season->name }}</h2></div><span class="badge text-bg-light">{{ $season->seasonLeagues->count() }} lig</span></div>
                            <div class="d-grid gap-2">@forelse($season->seasonLeagues->sortBy('league.level') as $seasonLeague)
                                <form method="POST" action="{{ route('admin.leagues.seasons.update', $seasonLeague) }}" class="border rounded-3 p-2"><div class="row g-2 align-items-center">@csrf @method('PUT')<div class="col-12"><strong>{{ $seasonLeague->league->name }}</strong></div><div class="col-5"><input class="form-control form-control-sm" type="number" min="0" name="promotion_places" value="{{ $seasonLeague->promotion_places }}" title="Awans"></div><div class="col-5"><input class="form-control form-control-sm" type="number" min="0" name="relegation_places" value="{{ $seasonLeague->relegation_places }}" title="Spadek"></div><div class="col-2"><button class="btn btn-outline-primary btn-sm" title="Zapisz">✓</button></div></div></form>
                            @empty<p class="text-muted-custom mb-0">Brak przypisanych lig.</p>@endforelse</div>
                        </div>
                    @endforeach
                </section>
            </div>
        </main>
    </div></div>
</div>
</body>
</html>
