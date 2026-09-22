<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Społeczność kibiców</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-layout">
    <aside class="sidebar d-flex flex-column p-3 p-lg-4" id="sidebar">
        <a class="brand d-flex align-items-center gap-2 mb-5" href="{{ route('news') }}"><span class="brand-mark">LP</span><span>#LechTYPER</span></a>
        <div class="sidebar-label px-2 mb-2">Nawigacja</div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link active d-flex align-items-center gap-3 px-3 py-2" href="{{ route('news') }}"><span class="nav-icon">⌂</span>Aktualności</a>
            @auth<a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('typer') }}"><span class="nav-icon">✎</span>Typer Lecha</a>@endauth
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('league.index') }}"><span class="nav-icon">⚽</span>Liga kiboli</a>
            @auth<a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('profile') }}"><span class="nav-icon">◎</span>Mój profil</a>@endauth
        </nav>
        @auth
        <div class="sidebar-label sidebar-label-spaced px-2 mb-2">Twoja strefa</div>
        <nav class="nav flex-column gap-1"><a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('profile') }}"><span class="nav-icon">◎</span>Mój profil</a><a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('profile') }}"><span class="nav-icon">⚙</span>Ustawienia</a></nav>
        @endauth
        <div class="sidebar-spacer"></div>
        <div class="match-mini p-3 mb-3"><div class="match-mini-top d-flex justify-content-between">Następny mecz <span class="live-dot"></span></div><div class="match-mini-teams d-flex justify-content-between my-3"><strong>LEC</strong><span>vs</span><strong>WIS</strong></div><div class="match-mini-date">Sobota, 20:30 <span>•</span> Enea Stadion</div></div>
        <div class="sidebar-profile d-flex align-items-center gap-2 border-top pt-3"><div class="avatar avatar-gold">MK</div><div><strong>Mateusz K.</strong><span>Kibol</span></div><button class="icon-button ms-auto" aria-label="Więcej">•••</button></div>
    </aside>

    <main class="main-content">
        <header class="topbar d-flex align-items-center justify-content-between px-3 px-lg-5"><button class="mobile-menu icon-button d-lg-none me-2" id="menu-toggle" aria-label="Otwórz menu">☰</button><div class="breadcrumb d-flex gap-3 mb-0"><span>{{ config('app.name') }}</span><b>/</b><strong>Aktualności</strong></div><div class="d-flex align-items-center gap-3">@auth <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="btn btn-sm btn-outline-secondary">Wyloguj</button></form>@else <a class="btn btn-sm btn-primary" href="{{ route('login') }}">Zaloguj się</a>@endauth</div></header>
        <div class="content-wrap container-fluid px-3 px-md-4 px-xl-5 py-4 py-lg-5">
            <section class="welcome-row d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
                <div>
                    <p class="eyebrow mb-2">Środa, 16 września 2026</p>
                    <h1 class="mb-2">Dzień dobry, <em class="text-blue">Mateusz.</em></h1>
                    <p class="text-muted-custom mb-0">Tu bije serce naszej społeczności. Zobacz, co dzieje się na trybunach.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('news') }}" class="primary-button btn px-3 py-2">Aktualności</a>
                    @auth
                        <a href="{{ route('league.index') }}" class="btn btn-outline-primary px-3 py-2">Liga kiboli</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-primary px-3 py-2">Zaloguj się</a>
                    @endauth
                </div>
            </section>

            <section class="hero-match position-relative p-4 p-lg-5 mb-4">
                <div class="hero-match-bg"></div>
                <div class="hero-match-content">
                        <div class="small text-white-50 mb-3"><span class="live-dot me-2"></span>Najbliższe typowanie <span class="mx-2">•</span>{{ $nextTyperMatch?->competition?->name ?? 'Mecz Lecha' }}</div>
                    <div class="hero-match-row d-flex align-items-center justify-content-center my-4">
                        <div class="club club-home d-flex align-items-center gap-2"><div class="club-crest crest-blue">L</div><span>{{ $nextTyperMatch?->lech_home ? 'Lech Poznań' : ($nextTyperMatch?->opponent ?? 'Lech Poznań') }}</span></div>
                        <div class="match-time text-center"><strong class="d-block">{{ $nextTyperMatch?->scheduled_at?->format('H:i') ?? '--:--' }}</strong><span class="small text-white-50">{{ $nextTyperMatch?->scheduled_at?->translatedFormat('l, d F') ?? 'Termin nieustalony' }}</span></div>
                        <div class="club d-flex align-items-center gap-2"><div class="club-crest crest-red">{{ $nextTyperMatch?->opponent ? substr($nextTyperMatch->opponent, 0, 1) : '?' }}</div><span>{{ $nextTyperMatch?->lech_home ? ($nextTyperMatch?->opponent ?? 'Rywal') : 'Lech Poznań' }}</span></div>
                    </div>
                    <div class="d-flex justify-content-between border-top border-light border-opacity-10 pt-3 small text-white-50">
                        <span>Typuj wynik i bonusy</span>
                        @auth<a class="text-white fw-semibold" href="{{ route('typer') }}">Otwórz Typera →</a>@else<a class="text-white fw-semibold" href="{{ route('login') }}">Zaloguj się →</a>@endauth
                    </div>
                </div>
            </section>

            <section class="stats-strip d-flex flex-wrap gap-3 mb-4">
                <div class="stat-box">
                    <span class="stat-label">Społeczność</span>
                    <strong>12,4 tys.</strong>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Mecze w lidze</span>
                    <strong>9 kolejka</strong>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Najbliższy mecz</span>
                    <strong>18:00</strong>
                </div>
            </section>

            @auth
                <section class="league-home-panel p-4 p-lg-5 mb-4">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <p class="eyebrow mb-2">Strefa kibola</p>
                            <h2 class="font-display h4 mb-2">Liga kiboli</h2>
                            <p class="text-muted-custom mb-0">Wybieraj pięciu zawodników Lecha i rywalizuj w swoim sezonie ligowym.</p>
                        </div>
                        <a class="btn btn-primary" href="{{ route('league.index') }}">Otwórz moją ligę</a>
                    </div>
                </section>
            @endauth

            <div class="content-grid">
                <div class="main-column">
                    <div class="d-flex justify-content-between align-items-end mb-3">
                        <div>
                            <p class="eyebrow mb-2">Z ostatniej chwili</p>
                            <h2 class="font-display h4 mb-0">Co słychać na trybunach?</h2>
                        </div>
                        <span class="filter-button">Najnowsze ⌄</span>
                    </div>

                    <article class="post-card p-3 p-md-4 mb-3" id="composer">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-gold">MK</div>
                            <div><strong>Mateusz K.</strong><span class="d-block text-muted-custom small">Teraz • Kibol</span></div>
                            <button class="icon-button ms-auto">•••</button>
                        </div>
                        <p class="post-placeholder p-3 my-3 rounded">Podziel się z ekipą tym, co masz na sercu...</p>
                        <div class="composer-actions d-flex gap-3">
                            <button>▧ Zdjęcie</button>
                            <button>☺ Nastrój</button>
                            <button class="composer-submit btn btn-sm text-white ms-auto" disabled>Opublikuj</button>
                        </div>
                    </article>

                    <article class="post-card p-3 p-md-4 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-red">KB</div>
                            <div><strong>Kolejorz Bez Granic</strong><span class="d-block text-muted-custom small">1 godz. temu • Kibol</span></div>
                            <button class="icon-button ms-auto">•••</button>
                        </div>
                        <p class="text-muted-custom my-3">W sobotę wszyscy na stadionie. Zbiórka pod Kaponierą o 18:00. Bierzcie szaliki, gardła i dobrą energię. <span class="text-blue fw-semibold">#DoBojuKolejorz</span></p>
                        <div class="post-image rounded overflow-hidden"><img src="https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=1000&q=85" alt="Kibice na stadionie podczas meczu" loading="lazy"></div>
                        <div class="d-flex justify-content-between text-muted-custom small py-3 border-bottom"><span class="text-danger">♥ 248</span><span>32 komentarze</span></div>
                        <div class="post-actions d-flex justify-content-between pt-3"><button class="reaction-button">♡ <span>Polub</span></button><button>◌ <span>Skomentuj</span></button><button>⌁ <span>Udostępnij</span></button></div>
                    </article>

                    <article class="post-card p-3 p-md-4 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-green">AD</div>
                            <div><strong>Admin Trybuny</strong><span class="d-block text-muted-custom small">3 godz. temu • <b class="text-success">Admin</b></span></div>
                            <button class="icon-button ms-auto">•••</button>
                        </div>
                        <p class="text-muted-custom my-3">Oficjalnie: ruszyła sprzedaż biletów na mecz z Wisłą. Sprawdźcie swoje skrzynki i nie czekajcie do ostatniej chwili.</p>
                        <div class="post-actions d-flex justify-content-between pt-2"><button class="reaction-button">♡ <span>Polub</span></button><button>◌ <span>Skomentuj</span></button><button>⌁ <span>Udostępnij</span></button></div>
                    </article>
                </div>

                <aside class="secondary-column">
                    <section class="side-card p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <p class="eyebrow mb-2">Sezon 2026/27</p>
                                <h3 class="font-display h5 mb-0">Tabela ligowa</h3>
                            </div>
                            <a class="text-blue small fw-semibold" href="{{ route('league.index') }}">Pełna tabela →</a>
                        </div>
                        <div class="row text-muted-custom small border-bottom pb-2">
                            <span class="col-1">#</span><span class="col">DRUŻYNA</span><span class="col-2 text-end">PKT</span>
                        </div>
                        @forelse($leaguePositions as $position)
                            @php $standing = $standings->get($position->team_id); @endphp
                            <div class="team-row row align-items-center py-2">
                                <b class="col-1">{{ $loop->iteration }}</b>
                                <span class="team-name col"><i class="team-dot {{ $loop->first ? 'lech' : '' }} me-2"></i>{{ $position->displayName() }}</span>
                                <strong class="col-2 text-end">{{ $standing?->points ?? 0 }}</strong>
                            </div>
                        @empty
                            <p class="text-muted-custom small mb-0 mt-3">Brak drużyn w tabeli.</p>
                        @endforelse
                    </section>

                    <section class="side-card p-3 p-md-4 mt-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <p class="eyebrow mb-2">{{ $season->name }}</p>
                                <h3 class="font-display h5 mb-0">Terminarz ligi</h3>
                            </div>
                            <a class="text-blue small fw-semibold" href="{{ route('league.index') }}">Pełny terminarz →</a>
                        </div>
                        <div class="d-grid gap-2">
                            @forelse($leagueMatches as $match)
                                <a href="{{ route('league.match', [$league->slug, $match->id]) }}" class="d-flex justify-content-between gap-3 py-2 border-bottom text-decoration-none">
                                    <span class="small"><span class="text-muted-custom d-block">Kolejka {{ $match->round_number }}</span>{{ $match->homeTeam->name }} - {{ $match->awayTeam->name }}</span>
                                    <span class="small text-muted-custom text-nowrap">{{ $match->scheduled_at->format('d.m H:i') }}</span>
                                </a>
                            @empty
                                <p class="text-muted-custom small mb-0">Brak zaplanowanych meczów.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="mini-panel">
                        <div class="premium-stat-head"><span>Premium</span><span class="pill pill-blue">+12%</span></div>
                        <div class="premium-stat-value">42,8k</div>
                        <div class="premium-stat-sub">widoków w tej edycji</div>
                    </section>

                    <section class="mini-panel">
                        <h3 class="mb-3">Statystyki</h3>
                        <ul>
                            <li><span>Średnia frekwencja</span><strong>31,6 tys.</strong></li>
                            <li><span>Aktywne typy</span><strong>1,240</strong></li>
                            <li><span>Nowi kibice</span><strong>+184</strong></li>
                        </ul>
                    </section>

                    <section class="quote-card p-4">
                        <p class="mb-2">„Nie ważne skąd jesteś. Ważne, że jesteś z nami.”</p>
                        <span class="small text-white-50">— Głos trybuny</span>
                    </section>
                </aside>
            </div>
        </div>
    </main>

    <aside class="right-rail d-none d-xl-block">
        <div class="right-rail-inner">
            <section class="premium-stat-card">
                <div class="premium-stat-head"><span>Premium</span><span class="pill pill-red">Live</span></div>
                <div class="premium-stat-value">29,3k</div>
                <div class="premium-stat-sub">aktywnych obserwujących</div>
            </section>
            <section class="mini-panel">
                <h3 class="mb-3">Statystyki sezonu</h3>
                <ul>
                    <li><span>Gole</span><strong>84</strong></li>
                    <li><span>Asysty</span><strong>52</strong></li>
                    <li><span>Kontuzje</span><strong>7</strong></li>
                </ul>
            </section>
            <section class="mini-panel">
                <h3 class="mb-3">Najpopularniejsze</h3>
                <ul>
                    <li><span>Lech vs Wisła</span><span class="pill pill-blue">Top</span></li>
                    <li><span>Typowanie tygodnia</span><span class="pill pill-red">Hot</span></li>
                    <li><span>Składy po deadline</span><span class="pill pill-blue">Nowe</span></li>
                </ul>
            </section>
        </div>
    </aside>
</div>
</body>
</html>
