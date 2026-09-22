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
                @auth<a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('typer') }}"><span class="nav-icon">✎</span>Typer Lecha</a>@endauth
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
                    <p class="text-muted-custom small mb-1">Twój rywal w tej kolejce</p>
                    <h1 class="font-display h2 mb-3">{{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</h1>
                    @if($match->leagueRound?->real_home_team)
                        <p class="mb-3">Typujesz wynik meczu: <strong>{{ $match->leagueRound->real_home_team }} - {{ $match->leagueRound->real_away_team }}</strong></p>
                    @else
                        <p class="text-muted-custom mb-3">Administrator nie przypisał jeszcze meczu Lecha do tej kolejki.</p>
                    @endif
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <p class="mb-1 text-muted-custom"><strong>Kolejka:</strong> {{ $match->round_number }}</p>
                            <p class="mb-0 text-muted-custom"><strong>Poziom rozgrywek:</strong> {{ $league->name }}</p>
                        </div>
                        <span class="badge {{ $match->status === 'scheduled' ? 'text-bg-light' : 'text-bg-success' }}">{{ $match->status === 'scheduled' ? 'Typowanie otwarte' : 'Mecz rozstrzygnięty' }}</span>
                    </div>
                </div>

                <div class="content-grid">
                    <div class="main-column">
                        @if(auth()->check() && $match->status === 'scheduled' && $match->scheduled_at->isFuture())
                            <div class="league-panel p-4 mb-4">
                                <p class="eyebrow mb-2">Typowanie</p>
                                <h2 class="font-display h4 mb-3">Typuj wynik meczu</h2>
                                @include('league.partials.prediction-form', ['match' => $match, 'selection' => $selection])
                            </div>
                        @elseif(auth()->check())
                            <div class="league-panel p-4 mb-4">
                                <p class="eyebrow mb-2">Typy</p>
                                <h2 class="font-display h4 mb-3">Twój zapisany typ</h2>
                                @if($selection)
                                    <p class="mb-0"><strong>{{ $selection->home_score }}:{{ $selection->away_score }}</strong></p>
                                @else
                                    <p class="text-muted-custom mb-0">Nie wytypowałeś tego meczu.</p>
                                @endif
                            </div>
                        @else
                            <div class="league-panel p-4 mb-4">
                                <p class="eyebrow mb-2">Publiczny podgląd</p>
                                <h2 class="font-display h4 mb-3">Zaloguj się, aby typować skład</h2>
                                <p class="text-muted-custom">Szczegóły meczu, data i terminarz są dostępne bez konta. Typowanie wymaga zalogowanego użytkownika.</p>
                                <a class="btn btn-primary" href="{{ route('login') }}">Zaloguj się</a>
                            </div>
                        @endif
                    </div>

                    <aside class="secondary-column">
                        <div class="mini-panel">
                            <div class="premium-stat-head"><span>Premium</span><span class="pill pill-red">Live</span></div>
                            <div class="premium-stat-value">16,8k</div>
                            <div class="premium-stat-sub">kibiców śledzi ten mecz</div>
                        </div>

                        <div class="mini-panel">
                            <h3 class="mb-3">Formy drużyn</h3>
                            <ul>
                                <li><span>{{ $match->homeTeam->name }}</span><strong>W-W-D</strong></li>
                                <li><span>{{ $match->awayTeam->name }}</span><strong>D-W-L</strong></li>
                            </ul>
                        </div>

                        <div class="mini-panel">
                            <h3 class="mb-3">Szczegóły spotkania</h3>
                            <ul>
                                <li><span>Data</span><strong>{{ $match->scheduled_at->format('d.m.Y') }}</strong></li>
                                <li><span>Godzina</span><strong>{{ $match->scheduled_at->format('H:i') }}</strong></li>
                                <li><span>Poziom</span><strong>{{ $league->name }}</strong></li>
                            </ul>
                        </div>
                    </aside>
                </div>
            </div>
        </main>

        <aside class="right-rail d-none d-xl-block">
            <div class="right-rail-inner">
                <section class="premium-stat-card">
                    <div class="premium-stat-head"><span>Premium</span><span class="pill pill-blue">Nowe</span></div>
                    <div class="premium-stat-value">12,9k</div>
                    <div class="premium-stat-sub">aktywnych typujących</div>
                </section>
                <section class="mini-panel">
                    <h3 class="mb-3">Forma</h3>
                    <ul>
                        <li><span>{{ $match->homeTeam->name }}</span><strong>5/6</strong></li>
                        <li><span>{{ $match->awayTeam->name }}</span><strong>4/6</strong></li>
                    </ul>
                </section>
                <section class="mini-panel">
                    <h3 class="mb-3">Wydarzenia</h3>
                    <ul>
                        <li><span>Typowanie</span><strong>Otwarte</strong></li>
                        <li><span>Składy</span><strong>Po terminie</strong></li>
                        <li><span>Premiery</span><strong>7</strong></li>
                    </ul>
                </section>
            </div>
        </aside>
    </div>
</div>
</body>
</html>
