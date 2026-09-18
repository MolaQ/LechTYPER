<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Terminarz</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="admin-shell"><div class="container-fluid"><div class="row min-vh-100">
    @include('admin.partials.sidebar')
    <main class="admin-content col-lg-10 p-3 p-md-4 p-xl-5">
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"><div><p class="eyebrow mb-2">Rozgrywki</p><h1 class="font-display h3 mb-1">Terminarz</h1><p class="text-muted-custom mb-0">Przypisz jeden realny mecz Lecha do każdej kolejki sezonu.</p></div><form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="btn btn-outline-secondary">Wyloguj</button></form></header>
        @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="bg-white border rounded-3 p-3 p-md-4 mb-4"><form method="GET" action="{{ route('admin.schedule.index') }}"><label class="form-label">Sezon</label><select class="form-select" name="season_id" onchange="this.form.submit()">@foreach($seasons as $availableSeason)<option value="{{ $availableSeason->id }}" @selected($availableSeason->id === $season->id)>{{ $availableSeason->name }} ({{ $availableSeason->status }})</option>@endforeach</select></form></section>
        <div class="d-grid gap-3">@foreach($season->seasonRounds as $round)<section class="bg-white border rounded-3 p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><p class="eyebrow mb-1">Kolejka {{ $round->round_number }}</p><h2 class="font-display h5 mb-0">{{ $round->real_home_team && $round->real_away_team ? $round->real_home_team.' - '.$round->real_away_team : 'Mecz Lecha do uzupełnienia' }}</h2></div><span class="badge text-bg-light">{{ $round->real_match_at?->format('d.m.Y H:i') ?? 'Bez daty' }}</span></div><form method="POST" action="{{ route('admin.schedule.rounds.update', $round) }}"><div class="row g-3">@csrf @method('PUT')<div class="col-md-6"><label class="form-label">Data i godzina meczu</label><input class="form-control" type="datetime-local" name="real_match_at" value="{{ $round->real_match_at?->format('Y-m-d\\TH:i') }}"></div><div class="col-md-6"><label class="form-label">Typ rozgrywek</label><select class="form-select" name="competition" required><option value="">Wybierz rozgrywki</option>@foreach(['Ekstraklasa', 'Puchar Polski', 'Mecz towarzyski', 'Liga Mistrzów', 'Liga Europy', 'Liga Konferencji'] as $competition)<option value="{{ $competition }}" @selected($round->competition === $competition)>{{ $competition }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Gospodarz</label><input class="form-control" name="real_home_team" value="{{ $round->real_home_team ?? 'Crystal Palace' }}" required></div><div class="col-md-6"><label class="form-label">Gość</label><input class="form-control" name="real_away_team" value="{{ $round->real_away_team ?? 'Lech Poznań' }}" required></div><div class="col-12"><button class="btn btn-primary">Zapisz mecz kolejki</button></div></div></form></section>@endforeach</div>
    </main>
</div></div></div>
</body>
</html>
