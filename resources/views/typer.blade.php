<!DOCTYPE html>
<html lang="pl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ config('app.name') }} | Typer</title>@vite(['resources/css/app.css', 'resources/js/app.js']) @livewireStyles</head>
<body><div class="app-shell"><div class="app-layout"><main class="main-content w-100"><header class="topbar d-flex align-items-center justify-content-between px-3 px-lg-5"><a class="brand" href="{{ route('home') }}">#LechTYPER</a><a class="btn btn-sm btn-outline-secondary" href="{{ route('league.index') }}">Liga kiboli</a></header><div class="content-wrap container-fluid px-3 px-md-4 px-xl-5 py-4 py-lg-5"><div class="mb-4"><p class="eyebrow mb-2">Typowanie Lecha</p><h1 class="font-display h2 mb-1">Twój typ na mecz</h1><p class="text-muted-custom mb-0">Wynik ustawiasz przyciskami, a bonusy wybierasz przełącznikami.</p></div><livewire:lech-typer.match-prediction /></div></main></div></div>@livewireScripts</body>
</html>
