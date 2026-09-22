<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ config('app.name') }} | Typer Lecha</title>
	@vite(['resources/css/app.css', 'resources/js/app.js'])
	@livewireStyles
</head>
<body>
<div class="app-layout">
	<aside class="sidebar d-flex flex-column p-3 p-lg-4" id="sidebar">
		<a class="brand d-flex align-items-center gap-2 mb-5" href="{{ route('home') }}">
			<span class="brand-mark">LP</span><span>#LechTYPER</span>
		</a>
		<div class="sidebar-label px-2 mb-2">Nawigacja</div>
		<nav class="nav flex-column gap-1">
			<a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('news') }}"><span class="nav-icon">⌂</span>Aktualności</a>
			<a class="nav-link active d-flex align-items-center gap-3 px-3 py-2" href="{{ route('typer') }}"><span class="nav-icon">✎</span>Typer Lecha</a>
			<a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('league.index') }}"><span class="nav-icon">⚽</span>Liga kiboli</a>
			@auth
				<a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('profile') }}"><span class="nav-icon">◎</span>Mój profil</a>
			@endauth
		</nav>
		<div class="sidebar-spacer"></div>
		<div class="sidebar-profile d-flex align-items-center gap-2 border-top pt-3">
			<div class="avatar avatar-gold">{{ substr(auth()->user()->name, 0, 1) }}</div>
			<div><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->role ?? 'Użytkownik' }}</span></div>
			<form method="POST" action="{{ route('logout') }}" class="ms-auto m-0">
				@csrf
				<button class="icon-button" aria-label="Wyloguj">⇥</button>
			</form>
		</div>
	</aside>

	<main class="main-content">
		<header class="topbar d-flex align-items-center justify-content-between px-3 px-lg-5">
			<button class="mobile-menu icon-button d-lg-none me-2" id="menu-toggle" aria-label="Otwórz menu">☰</button>
			<div class="breadcrumb d-flex gap-3 mb-0"><span>{{ config('app.name') }}</span><b>/</b><strong>Typer Lecha</strong></div>
			<a class="btn btn-sm btn-outline-secondary" href="{{ route('league.index') }}">Liga kiboli</a>
		</header>
		<div class="content-wrap container-fluid px-3 px-md-4 px-xl-5 py-4 py-lg-5">
			<div class="mb-4">
				<p class="eyebrow mb-2">Typowanie Lecha</p>
				<h1 class="font-display h2 mb-1">Twój typ na mecz</h1>
				<p class="text-muted-custom mb-0">Wynik ustawiasz centralnie, a pytania bonusowe znajdziesz pod formularzem.</p>
			</div>
			<livewire:lech-typer.match-prediction />
		</div>
	</main>
</div>
@livewireScripts
</body>
</html>
