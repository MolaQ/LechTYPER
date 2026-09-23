<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | {{ $league->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .promotion-row { background: #eaf7ee !important; }
        .relegation-row { background: #fff0ef !important; }
        .promotion-row td:first-child { border-left: 3px solid #3b9b55; }
        .relegation-row td:first-child { border-left: 3px solid #c95b56; }
    </style>
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
            <div class="match-mini p-3 mb-3">
                <div class="match-mini-top d-flex justify-content-between">Następny mecz <span class="live-dot"></span></div>
                <div class="match-mini-teams d-flex justify-content-between my-3"><strong>LEC</strong><span>vs</span><strong>WIS</strong></div>
                <div class="match-mini-date">Sobota, 20:30 <span>•</span> Enea Stadion</div>
            </div>
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
                <div class="d-flex align-items-center gap-3">
                    @auth
                        <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="btn btn-sm btn-outline-secondary">Wyloguj</button></form>
                    @else
                        <a class="btn btn-sm btn-primary" href="{{ route('login') }}">Zaloguj się</a>
                    @endauth
                </div>
            </header>

            <div class="content-wrap container-fluid px-3 px-md-4 px-xl-5 py-4 py-lg-5">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <p class="eyebrow mb-2">{{ $season->name }}</p>
                        <h1 class="font-display h2 mb-1">Liga kiboli</h1>
                        <p class="text-muted-custom mb-0">{{ $team?->name ?? 'Publiczny podgląd' }} · {{ $league->name }}</p>
                    </div>
                    <div class="league-selector">
                        @foreach($season->seasonLeagues->sortBy('league.level') as $item)
                            <a href="{{ route('league.show', $item->league->slug) }}" class="btn btn-sm {{ $item->league_id === $league->id ? 'btn-primary' : 'btn-outline-primary' }}">{{ $item->league->name }}</a>
                        @endforeach
                    </div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success mb-4">{{ session('status') }}</div>
                @endif

                <div class="content-grid">
                    <section class="main-column">
                        <div class="league-panel table-panel p-4 mb-4">
                            <p class="eyebrow mb-2">Typowanie Lecha</p>
                            <h2 class="font-display h4 mb-2">Typowanie jest w module LechTYPER</h2>
                            <p class="text-muted-custom mb-3">Wynik meczu Lecha i pytania bonusowe znajdziesz na osobnej stronie Typera.</p>
                            <a class="btn btn-primary" href="{{ route('typer') }}">Przejdź do Typera Lecha</a>
                        </div>

                        <div class="league-panel p-4 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <p class="eyebrow mb-2">Tabela</p>
                                    <h2 class="font-display h4 mb-0">{{ $league->name }}</h2>
                                </div>
                                <span class="badge text-bg-light">{{ method_exists($standings, 'total') ? $standings->total() : $standings->count() }} drużyn</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Drużyna</th>
                                            <th>M</th>
                                            <th>Z</th>
                                            <th>R</th>
                                            <th>PKT</th>
                                            <th>Bilans</th>
                                            <th>Bonus</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($standings as $standing)
                                            @php
                                                $position = method_exists($standings, 'firstItem') ? $standings->firstItem() + $loop->index : $loop->iteration;
                                                $promotionPlaces = (int) $seasonLeague->promotion_places;
                                                $relegationPlaces = (int) $seasonLeague->relegation_places;
                                                $promotionClass = $promotionPlaces > 0 && $position <= $promotionPlaces ? 'promotion-row' : '';
                                                $standingTotal = method_exists($standings, 'total') ? $standings->total() : $standings->count();
                                                $relegationClass = $relegationPlaces > 0 && $position > $standingTotal - $relegationPlaces ? 'relegation-row' : '';
                                            @endphp
                                            <tr class="{{ $team && $standing->team_id === $team->id ? 'table-primary' : ($promotionClass ?: $relegationClass) }}">
                                                <td>{{ $position }}</td>
                                                <td>{{ $standing->team->name }}</td>
                                                <td>{{ $standing->played }}</td>
                                                <td>{{ $standing->wins }}</td>
                                                <td>{{ $standing->draws }}</td>
                                                <td><strong>{{ $standing->points }}</strong></td>
                                                <td>{{ $standing->score_for }}:{{ $standing->score_against }}</td>
                                                <td>{{ $standing->bonus_points }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(method_exists($standings, 'hasPages') && $standings->hasPages())
                                <div class="mt-3">{{ $standings->links() }}</div>
                            @endif
                        </div>

                        <div class="league-panel fixture-panel p-4">
                            <p class="eyebrow mb-2">Terminarz</p>
                            <h2 class="font-display h4 mb-3">Kolejki i mecze</h2>
                            <div class="d-grid gap-3">
                                @forelse($seasonRounds as $seasonRound)
                                    @php
                                        $roundMatches = $matches->where('round_number', $seasonRound->round_number);
                                        $typerMatch = $typerMatchesByRound->get($seasonRound->round_number);
                                        $assignedRealMatch = $seasonRound->realMatch ?? null;
                                        $leagueRealMatchLabel = $seasonRound->real_home_team
                                            ? $seasonRound->real_home_team.' - '.$seasonRound->real_away_team
                                            : null;
                                        $realMatchLabel = $assignedRealMatch
                                            ? $assignedRealMatch->home_team.' - '.$assignedRealMatch->away_team
                                            : ($leagueRealMatchLabel
                                                ? $leagueRealMatchLabel
                                                : ($typerMatch ? ($typerMatch->lech_home ? 'Lech Poznań - '.$typerMatch->opponent : $typerMatch->opponent.' - Lech Poznań') : null));
                                    @endphp
                                    <div class="border rounded-3 p-3">
                                        <div class="d-flex justify-content-between align-items-center gap-3 mb-2"><strong>Kolejka {{ $seasonRound->round_number }}</strong>@if($typerMatch)<a class="small text-muted-custom" href="{{ route('league.real-match', [$league->slug, $typerMatch->id]) }}">{{ $realMatchLabel }}</a>@else<span class="small text-muted-custom">{{ $realMatchLabel ?? 'Mecz Lecha nieprzypisany' }}</span>@endif</div>
                                        @if($assignedRealMatch || $leagueRealMatchLabel || $typerMatch)<small class="text-muted-custom d-block mb-2">{{ $assignedRealMatch?->competition ?? $seasonRound->competition ?? $typerMatch?->competition?->name }} · źródło punktacji typów</small>@endif
                                        <div class="d-grid gap-2">
                                            @forelse($roundMatches as $match)
                                                <a href="{{ route('league.match', [$league->slug, $match->id]) }}" class="match-row d-flex justify-content-between align-items-center p-2 rounded-3 border text-decoration-none">
                                                    <strong>{{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</strong>
                                                    <div class="text-end">
                                                        <small class="d-block text-muted-custom">{{ $match->scheduled_at->format('d.m.Y H:i') }}</small>
                                                        @if($match->status === 'scheduled')
                                                            <span class="badge text-bg-light mt-1">Typowanie</span>
                                                        @else
                                                            <span class="badge text-bg-success mt-1">{{ $match->home_score }}:{{ $match->away_score }}</span>
                                                        @endif
                                                    </div>
                                                </a>
                                            @empty
                                                <p class="small text-muted-custom mb-0">Brak par w tej kolejce.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted-custom mb-0">Brak zaplanowanych meczów dla tej ligi.</p>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <aside class="secondary-column">
                        <div class="league-panel p-4 mb-4">
                            <p class="eyebrow mb-3">Typer Lecha</p>
                            <p class="text-muted-custom mb-3">Typy meczów Lecha są dostępne w module LechTYPER.</p>
                            @auth
                                <a class="btn btn-outline-primary btn-sm" href="{{ route('typer') }}">Otwórz Typera</a>
                            @else
                                <a class="btn btn-outline-primary btn-sm" href="{{ route('login') }}">Zaloguj się do Typera</a>
                            @endauth
                        </div>

                        <div class="league-panel p-4">
                            <p class="eyebrow mb-3">Punktacja</p>
                            @if($myTeamStanding)
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-3">
                                    <span>Pozycja</span>
                                    <strong>#{{ $myTeamStanding->position ?? '-' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-3">
                                    <span>Punkty</span>
                                    <strong>{{ $myTeamStanding->points }}</strong>
                                </div>
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-3">
                                    <span>Bilans</span>
                                    <strong>{{ $myTeamStanding->score_for }}:{{ $myTeamStanding->score_against }}</strong>
                                </div>
                            @else
                                <p class="text-muted-custom mb-0">Nie ma jeszcze danych dla Twojego zespołu w tej lidze.</p>
                            @endif
                        </div>

                        @auth
                        @if(auth()->user()->hasActivePremium())
                        <div class="mini-panel">
                            <div class="premium-stat-head"><span>Premium</span><span class="pill pill-blue">+9%</span></div>
                            <div class="premium-stat-value">14,7k</div>
                            <div class="premium-stat-sub">aktywnych kibiców w lidze</div>
                        </div>
                        @else
                        <div class="mini-panel">
                            <div class="premium-stat-head"><span>Premium</span><span class="pill pill-red">Zablokowane</span></div>
                            <strong>Odblokuj statystyki ligi</strong>
                            <p class="text-muted-custom small mt-2 mb-2">Pełne porównania i analizy są dostępne w Premium.</p>
                            <a class="btn btn-primary btn-sm" href="{{ route('premium') }}">Sprawdź Premium</a>
                        </div>
                        @endif
                        @endauth

                        <div class="mini-panel">
                            <h3 class="mb-3">Statystyki ligi</h3>
                            <ul>
                                <li><span>Średnia bramek</span><strong>2.4</strong></li>
                                <li><span>Typowania</span><strong>842</strong></li>
                                <li><span>Wynik meczu</span><strong>3:1</strong></li>
                            </ul>
                        </div>
                    </aside>
                </div>
            </div>
        </main>

        <aside class="right-rail d-none d-xl-block">
            <div class="right-rail-inner">
                @auth
                @if(auth()->user()->hasActivePremium())
                <section class="premium-stat-card">
                    <div class="premium-stat-head"><span>Premium</span><span class="pill pill-red">Live</span></div>
                    <div class="premium-stat-value">21,4k</div>
                    <div class="premium-stat-sub">kibiców w trybie premium</div>
                </section>
                @else
                <section class="premium-stat-card">
                    <div class="premium-stat-head"><span>Premium</span><span class="pill pill-red">Zablokowane</span></div>
                    <div class="premium-stat-value">?</div>
                    <div class="premium-stat-sub">Odblokuj statystyki ligi</div>
                    <a class="btn btn-light btn-sm mt-3" href="{{ route('premium') }}">Sprawdź Premium</a>
                </section>
                @endif
                @endauth
                <section class="mini-panel">
                    <h3 class="mb-3">Największe rywalizacje</h3>
                    <ul>
                        <li><span>Lech - Wisła</span><strong>84%</strong></li>
                        <li><span>Jagiellonia</span><strong>71%</strong></li>
                        <li><span>Raków</span><strong>69%</strong></li>
                    </ul>
                </section>
                <section class="mini-panel">
                    <h3 class="mb-3">Szczegóły meczu</h3>
                    <ul>
                        <li><span>Data</span><strong>{{ $nextMatch ? $nextMatch->scheduled_at->format('d.m') : '---' }}</strong></li>
                        <li><span>Stadion</span><strong>Enea</strong></li>
                        <li><span>Typowanie</span><strong>Otwarte</strong></li>
                    </ul>
                </section>
            </div>
        </aside>
    </div>
</div>
</body>
</html>
