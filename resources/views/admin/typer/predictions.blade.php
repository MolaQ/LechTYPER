<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Typy - {{ $match->opponent }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .prediction-cell { min-width: 54px; text-align: center; }
        .prediction-cell span { display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 6px; font-size: 12px; font-weight: 700; }
        .prediction-cell.is-correct span { background: #e5f4e9; color: #287a3d; }
        .prediction-cell.is-wrong span { background: #fbe8e7; color: #a63d39; }
        .prediction-cell.is-skipped span { background: #fff4d6; color: #8a6815; }
        .prediction-cell.is-pending span { background: #f1f3f5; color: #6c757d; }
        .prediction-group { border-left: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="admin-shell"><div class="container-fluid"><div class="row min-vh-100">
    @include('admin.partials.sidebar')
    <main class="admin-content col-lg-10 p-3 p-md-4 p-xl-5">
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div><p class="eyebrow mb-2">{{ $match->competition->name }}{{ $match->round_number ? ' · Kolejka '.$match->round_number : '' }}</p><h1 class="font-display h3 mb-1">Typy: {{ $match->lech_home ? 'Lech Poznań - '.$match->opponent : $match->opponent.' - Lech Poznań' }}</h1><p class="text-muted-custom mb-0">{{ $match->scheduled_at->format('d.m.Y H:i') }}</p></div>
            <a class="btn btn-outline-primary" href="{{ route('admin.typer.index') }}">Wróć do meczów</a>
        </header>
        <div class="alert alert-light border small">Legenda: <span class="ms-2 px-2 py-1 rounded" style="background:#e5f4e9;color:#287a3d">poprawna</span><span class="ms-2 px-2 py-1 rounded" style="background:#fbe8e7;color:#a63d39">błędna</span><span class="ms-2 px-2 py-1 rounded" style="background:#fff4d6;color:#8a6815">pominięta</span></div>
        <section class="bg-white border rounded-3 p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="font-display h5 mb-0">Odpowiedzi typujących</h2><span class="badge text-bg-light">{{ $predictions->count() }} typów</span></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th rowspan="2">Kibic</th><th rowspan="2">Wynik</th><th colspan="{{ $offensiveQuestions->count() + 1 }}" class="text-center">Pytania ofensywne</th><th colspan="{{ $defensiveQuestions->count() + 1 }}" class="text-center prediction-group">Pytania defensywne</th><th rowspan="2">Suma</th></tr>
                        <tr>@foreach($offensiveQuestions as $question)<th class="prediction-cell" title="{{ $question->question_text }}">{{ $loop->iteration }}</th>@endforeach<th class="text-center">Suma</th>@foreach($defensiveQuestions as $question)<th class="prediction-cell prediction-group" title="{{ $question->question_text }}">{{ $loop->iteration }}</th>@endforeach<th class="text-center">Suma</th></tr>
                    </thead>
                    <tbody>
                    @forelse($predictions as $prediction)
                        @php
                            $questionIds = $offensiveQuestions->concat($defensiveQuestions)->pluck('id');
                            $answers = $prediction->answers->whereIn('bonus_question_id', $questionIds)->keyBy('bonus_question_id');
                            $defensivePoints = 0;
                        @endphp
                        <tr>
                            <td>{{ $prediction->user->name }}</td>
                            <td><strong>{{ $prediction->home_score }}:{{ $prediction->away_score }}</strong><div class="small text-muted-custom">{{ $prediction->points_base }} pkt</div></td>
                            @foreach($offensiveQuestions as $question)
                                @php($answer = $answers->get($question->id))
                                @php($status = $answer === null || $question->correct_answer === null ? 'pending' : ($answer->answer === null ? 'skipped' : ($answer->answer === $question->correct_answer ? 'correct' : 'wrong')))
                                <td class="prediction-cell is-{{ $status }}" title="{{ $question->question_text }}"><span>{{ $answer === null || $answer->answer === null ? '—' : ($answer->answer ? 'T' : 'N') }}</span></td>
                            @endforeach
                            <td class="text-center"><strong>{{ $prediction->points_offensive }}</strong></td>
                            @foreach($defensiveQuestions as $question)
                                @php($answer = $answers->get($question->id))
                                @php($status = $answer === null || $question->correct_answer === null ? 'pending' : ($answer->answer === null ? 'skipped' : ($answer->answer === $question->correct_answer ? 'correct' : 'wrong')))
                                @if($status === 'correct' && $answer->answer === true) @php($defensivePoints++) @endif
                                <td class="prediction-cell prediction-group is-{{ $status }}" title="{{ $question->question_text }}"><span>{{ $answer === null || $answer->answer === null ? '—' : ($answer->answer ? 'T' : 'N') }}</span></td>
                            @endforeach
                            <td class="text-center"><strong>{{ $defensivePoints }}</strong></td>
                            <td><strong>{{ $prediction->total_points }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="20" class="text-muted-custom">Nikt jeszcze nie wytypował tego meczu.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div></div></div>
</body>
</html>
