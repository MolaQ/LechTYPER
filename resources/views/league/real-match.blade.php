<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | {{ $match->opponent }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-layout">
    <aside class="sidebar d-flex flex-column p-3 p-lg-4" id="sidebar">
        <a class="brand d-flex align-items-center gap-2 mb-5" href="{{ route('home') }}"><span class="brand-mark">LP</span><span>#LechTYPER</span></a>
        <div class="sidebar-label px-2 mb-2">Nawigacja</div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('news') }}"><span class="nav-icon">⌂</span>Aktualności</a>
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2" href="{{ route('typer') }}"><span class="nav-icon">✎</span>Typer Lecha</a>
            <a class="nav-link active d-flex align-items-center gap-3 px-3 py-2" href="{{ route('league.index') }}"><span class="nav-icon">⚽</span>Liga kiboli</a>
        </nav>
    </aside>
    <main class="main-content">
        <header class="topbar d-flex align-items-center justify-content-between px-3 px-lg-5"><div class="breadcrumb d-flex gap-3 mb-0"><span>{{ config('app.name') }}</span><b>/</b><strong>Wynik meczu Lecha</strong></div><a class="btn btn-sm btn-outline-primary" href="{{ route('league.show', $league->slug) }}">Wróć do ligi</a></header>
        <div class="content-wrap container-fluid px-3 px-md-4 px-xl-5 py-4 py-lg-5">
            <div class="league-panel p-4 p-lg-5">
                <p class="eyebrow mb-2">{{ $match->competition->name }} · Kolejka {{ $match->round_number }}</p>
                <h1 class="font-display h2 mb-2">{{ $match->lech_home ? 'Lech Poznań - '.$match->opponent : $match->opponent.' - Lech Poznań' }}</h1>
                <p class="text-muted-custom mb-3">{{ $match->scheduled_at->format('d.m.Y, H:i') }}</p>
                @if($match->status === 'completed')
                    <div class="display-5 mb-4"><strong>{{ $match->result_home }}:{{ $match->result_away }}</strong></div>
                    <h2 class="font-display h4 mb-3">Poprawne odpowiedzi bonusowe</h2>
                    <div class="row g-3">
                        @foreach($match->bonusQuestions as $question)
                            <div class="col-md-6"><div class="border rounded-3 p-3"><span class="small text-muted-custom">{{ $question->type === 'offensive' ? 'Ofensywne' : 'Defensywne' }}</span><div class="mt-1">{{ $question->question_text }}</div><strong class="d-block mt-2">{{ $question->correct_answer === null ? 'Brak rozstrzygnięcia' : ($question->correct_answer ? 'TAK' : 'NIE') }}</strong></div></div>
                        @endforeach
                    </div>
                @else
                    <span class="badge text-bg-light">Mecz jeszcze nierozliczony</span>
                @endif
            </div>
        </div>
    </main>
</div>
</body>
</html>
