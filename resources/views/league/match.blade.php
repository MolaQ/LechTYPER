<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | {{ $league->name }} · {{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    <div class="app-layout">
        <aside class="sidebar d-flex flex-column p-3 p-lg-4" id="sidebar">
            <a class="brand d-flex align-items-center gap-2 mb-5" href="{{ route('news') }}"><span class="brand-mark">LP</span><span>#LechTYPER</span></a>
            <div class="sidebar-label px-2 mb-2">Nawigacja</div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('news') }}"><span class="nav-icon">⌂</span>Aktualności</a>
                <a class="nav-link active d-flex align-items-center gap-3 px-3 py-2" href="{{ route('league.index') }}"><span class="nav-icon">⚽</span>Liga kiboli</a>
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('profile') }}"><span class="nav-icon">◎</span>Mój profil</a>
            </nav>
            <div class="sidebar-spacer"></div>
            <div class="sidebar-profile d-flex align-items-center gap-2 border-top pt-3">
                <div class="avatar avatar-gold">MK</div>
                <div><strong>{{ auth()->user()?->name ?? 'Kibol' }}</strong><span>{{ auth()->user()?->role ?? 'Użytkownik' }}</span></div>
                <form method="POST" action="{{ route('logout') }}" class="ms-auto m-0">@csrf<button class="icon-button" aria-label="Wyloguj">⇥</button></form>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar d-flex align-items-center justify-content-between px-3 px-lg-5">
                <button class="mobile-menu icon-button d-lg-none me-2" id="menu-toggle" aria-label="Otwórz menu">☰</button>
                <div class="breadcrumb d-flex gap-3 mb-0"><span>{{ config('app.name') }}</span><b>/</b><strong>{{ $league->name }}</strong></div>
                <a href="{{ route('league.show', $league->slug) }}" class="btn btn-sm btn-outline-primary">Powrót do ligi</a>
            </header>

            <div class="content-wrap container-fluid px-3 px-md-4 px-xl-5 py-4 py-lg-5">
                <div class="league-panel p-4 p-lg-5 mb-4">
                    <p class="eyebrow mb-2">{{ $match->scheduled_at->format('d.m.Y, H:i') }}</p>
                    <h1 class="font-display h2 mb-3">{{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</h1>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <p class="mb-1 text-muted-custom"><strong>Kolejka:</strong> {{ $match->round_number }}</p>
                            <p class="mb-0 text-muted-custom"><strong>Poziom rozgrywek:</strong> {{ $league->name }}</p>
                        </div>
                        <span class="badge {{ $match->status === 'scheduled' ? 'text-bg-light' : 'text-bg-success' }}">{{ $match->status === 'scheduled' ? 'Typowanie otwarte' : 'Mecz rozstrzygnięty' }}</span>
                    </div>
                </div>

                @if($match->status === 'scheduled' && $match->scheduled_at->isFuture())
                    <div class="league-panel p-4 mb-4">
                        <p class="eyebrow mb-2">Typowanie</p>
                        <h2 class="font-display h4 mb-3">Wybierz skład na ten mecz</h2>
                        <form method="POST" action="{{ route('league.selection.store', $match) }}">
                            @csrf
                            <div class="row g-2">
                                @foreach($players as $teamPlayer)
                                    <div class="col-sm-6 col-xl-4">
                                        <label class="player-option d-flex align-items-center gap-2 p-2">
                                            <input type="checkbox" name="players[]" value="{{ $teamPlayer->player_id }}" @checked($selection?->players->contains('player_id', $teamPlayer->player_id)) @disabled($teamPlayer->isInjured())>
                                            <span>{{ $teamPlayer->player->name }}</span>
                                            @if($teamPlayer->isInjured())
                                                <span class="injury-mark" title="Kontuzja do {{ $teamPlayer->injury_until->format('d.m.Y H:i') }}">✕</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <button class="btn btn-primary mt-3" type="submit">Zapisz 5 zawodników</button>
                        </form>
                    </div>
                @else
                    <div class="league-panel p-4 mb-4">
                        <p class="eyebrow mb-2">Składy</p>
                        <h2 class="font-display h4 mb-3">Tymczasowo pokazujemy składy rywali po zamknięciu typowania</h2>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <h3 class="h5 mb-3">{{ $match->homeTeam->name }}</h3>
                                    <ul class="list-unstyled mb-0">
                                        <li class="py-2 border-bottom">Lewy obrońca</li>
                                        <li class="py-2 border-bottom">Środkowy pomocnik</li>
                                        <li class="py-2 border-bottom">Napastnik</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <h3 class="h5 mb-3">{{ $match->awayTeam->name }}</h3>
                                    <ul class="list-unstyled mb-0">
                                        <li class="py-2 border-bottom">Prawy obrońca</li>
                                        <li class="py-2 border-bottom">Ofensywny pomocnik</li>
                                        <li class="py-2 border-bottom">Napastnik</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </main>
    </div>
</div>
</body>
</html>
