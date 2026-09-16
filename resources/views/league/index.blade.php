<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Liga</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="container py-4 py-lg-5 league-page">
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><a class="brand d-flex align-items-center gap-2 mb-3" href="{{ route('home') }}"><span class="brand-mark">LP</span><span>#LechTYPER</span></a><p class="eyebrow mb-2">{{ $season->name }}</p><h1 class="font-display h2 mb-1">Twoja liga</h1><p class="text-muted-custom mb-0">{{ $team->name }} · {{ $seasonTeam?->seasonLeague?->league?->name ?? 'Liga podwórkowa' }}</p></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-secondary">Wyloguj</button></form>
    </header>
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="row g-4">
        <section class="col-lg-8">
            <div class="league-panel p-4 mb-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><p class="eyebrow mb-2">Najbliższe spotkanie</p><h2 class="font-display h4 mb-0">{{ $nextMatch ? $nextMatch->homeTeam->name.' - '.$nextMatch->awayTeam->name : 'Terminarz jest jeszcze pusty' }}</h2></div>@if($nextMatch)<span class="badge text-bg-light">Kolejka {{ $nextMatch->round_number }}</span>@endif</div>@if($nextMatch)<p class="text-muted-custom small">Typowanie do {{ $nextMatch->scheduled_at->format('d.m.Y, H:i') }}</p><form method="POST" action="{{ route('league.selection.store', $nextMatch) }}">@csrf<div class="row g-2">@foreach($players as $teamPlayer)<div class="col-sm-6 col-xl-4"><label class="player-option d-flex align-items-center gap-2 p-2"><input type="checkbox" name="players[]" value="{{ $teamPlayer->player_id }}" @checked($selection?->players->contains('player_id', $teamPlayer->player_id)) @disabled($teamPlayer->isInjured())><span>{{ $teamPlayer->player->name }}</span>@if($teamPlayer->isInjured())<span class="injury-mark" title="Kontuzja do {{ $teamPlayer->injury_until->format('d.m.Y H:i') }}">✕</span>@endif</label></div>@endforeach</div><button class="btn btn-primary mt-3" type="submit">Zapisz 5 zawodników</button></form>@else<p class="text-muted-custom mb-0">Administrator nie utworzył jeszcze meczu dla Twojej ligi.</p>@endif</div>
            <div class="league-panel p-4"><p class="eyebrow mb-2">Reguły sezonu</p><h2 class="font-display h4">9 kolejek · każdy z każdym</h2><p class="text-muted-custom mb-0">Wygrana daje 3 punkty, remis 1, a porażka 0. Po sezonie system automatycznie obsłuży awanse i spadki.</p></div>
        </section>
        <aside class="col-lg-4"><div class="league-panel p-4"><p class="eyebrow mb-3">Twój skład Lecha</p>@forelse($players as $teamPlayer)<div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>{{ $teamPlayer->player->name }}</span>@if($teamPlayer->isInjured())<span class="injury-mark" title="Kontuzja do {{ $teamPlayer->injury_until->format('d.m.Y H:i') }}">✕</span>@endif</div>@empty<p class="text-muted-custom mb-0">Administrator nie wprowadził jeszcze aktualnego składu Lecha Poznań.</p>@endforelse</div></aside>
    </div>
</main>
</body>
</html>
