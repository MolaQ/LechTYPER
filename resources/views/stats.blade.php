<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Statystyki Premium</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="container py-5"><a class="brand d-flex align-items-center gap-2 mb-5" href="{{ route('home') }}"><span class="brand-mark">LP</span><span>#LechTYPER</span></a><div class="d-flex justify-content-between align-items-end mb-4"><div><p class="eyebrow mb-2">Strefa Premium</p><h1 class="font-display h2 mb-2">Statystyki typowania</h1><p class="text-muted-custom mb-0">Dostęp dla użytkowników z aktywnym Premium.</p></div><span class="badge rounded-pill text-bg-warning">Premium</span></div><div class="row g-3"><div class="col-md-4"><div class="stat-card bg-white p-4"><p class="text-muted-custom small">Skuteczność typów</p><strong class="stat-value">68%</strong></div></div><div class="col-md-4"><div class="stat-card bg-white p-4"><p class="text-muted-custom small">Miejsce w rankingu</p><strong class="stat-value">#24</strong></div></div><div class="col-md-4"><div class="stat-card bg-white p-4"><p class="text-muted-custom small">Punkty sezonu</p><strong class="stat-value">842</strong></div></div></div><a class="btn btn-outline-primary mt-4" href="{{ route('home') }}">Wróć na stronę główną</a></main>
</body>
</html>
