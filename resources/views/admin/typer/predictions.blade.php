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
        @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="bg-white border rounded-3 p-3 p-md-4 mb-4">
            <p class="eyebrow mb-2">Ustawienia meczu</p>
            <h2 class="font-display h5 mb-3">Data, godzina i kolejka</h2>
            <form method="POST" action="{{ route('admin.typer.matches.update', $match) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="competition_id" value="{{ $match->competition_id }}">
                <input type="hidden" name="opponent" value="{{ $match->opponent }}">
                <input type="hidden" name="lech_home" value="{{ $match->lech_home ? 1 : 0 }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Data i godzina</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ $match->scheduled_at->format('Y-m-d\TH:i') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kolejka</label>
                        <select name="round_number" class="form-select">
                            <option value="">Bez przypisania</option>
                            @foreach($seasonRounds as $seasonRound)
                                <option value="{{ $seasonRound->round_number }}" @selected($match->round_number === $seasonRound->round_number)>Kolejka {{ $seasonRound->round_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-outline-primary">Zapisz ustawienia meczu</button>
                    </div>
                </div>
            </form>
        </section>
        <section class="bg-white border rounded-3 p-3 p-md-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3"><div><p class="eyebrow mb-2">Pytania bonusowe</p><h2 class="font-display h5 mb-0">Zestaw pytań dla tego meczu</h2></div><span class="badge text-bg-light">{{ $match->bonusQuestions->count() }} / 10</span></div>
            <form method="POST" action="{{ route('admin.bonuses.assign', $match) }}">@csrf
                <div class="row g-3">
                    @foreach(['offensive' => 'Ofensywne', 'defensive' => 'Defensywne'] as $type => $label)
                        <div class="col-lg-6">
                            <h3 class="font-display h6">{{ $label }}</h3>
                            @foreach(range(0, 4) as $slot)
                                @php
                                    $selectedQuestion = $match->bonusQuestions->where('type', $type)->values()->get($slot);
                                    $selectedId = $selectedQuestion?->pool_question_id;
                                @endphp
                                <select name="{{ $type }}[]" class="form-select mb-2">
                                    <option value="">Wybierz pytanie {{ $slot + 1 }}</option>
                                    @foreach($questions as $question)
                                        <option value="{{ $question->id }}" @selected($selectedId === $question->id)>{{ $question->question_text }}</option>
                                    @endforeach
                                </select>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                <div class="d-flex gap-2 mt-2"><button class="btn btn-outline-primary">Zapisz pytania</button><button class="btn btn-primary" formaction="{{ route('admin.bonuses.draw', $match) }}">Losuj puste miejsca</button></div>
            </form>
        </section>
        <section class="bg-white border rounded-3 p-3 p-md-4 mb-4">
            <p class="eyebrow mb-2">Rozliczenie meczu</p><h2 class="font-display h5 mb-3">Wynik i poprawne odpowiedzi</h2>
            <form method="POST" action="{{ route('admin.typer.matches.result.update', $match) }}">@csrf @method('PATCH')
                <div class="row g-3"><div class="col-md-2"><label class="form-label">Wynik Lecha</label><input name="result_home" type="number" min="0" max="99" class="form-control" value="{{ $match->result_home }}" required></div><div class="col-md-2"><label class="form-label">Wynik rywala</label><input name="result_away" type="number" min="0" max="99" class="form-control" value="{{ $match->result_away }}" required></div><div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="scheduled" @selected($match->status === 'scheduled')>Zaplanowany</option><option value="completed" @selected($match->status === 'completed')>Zakończony</option><option value="cancelled" @selected($match->status === 'cancelled')>Odwołany</option></select></div></div>
                @if($match->bonusQuestions->isNotEmpty())
                    <div class="row g-3 border-top mt-4 pt-3">
                        @foreach($match->bonusQuestions as $question)
                            <div class="col-lg-6">
                                <label class="form-label small">{{ $question->question_text }} <span class="text-muted-custom">({{ $question->type === 'offensive' ? 'ofensywne' : 'defensywne' }})</span></label>
                                <select name="correct_answers[{{ $question->id }}]" class="form-select">
                                    <option value="">Brak rozstrzygnięcia</option>
                                    <option value="1" @selected($question->correct_answer === true)>TAK</option>
                                    <option value="0" @selected($question->correct_answer === false && $question->correct_answer !== null)>NIE</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                @endif
                <button class="btn btn-primary mt-3">Zapisz wynik i rozlicz typy</button>
            </form>
        </section>
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
                                @php
                                    $answer = $answers->get($question->id);
                                    $status = $answer === null || $question->correct_answer === null ? 'pending' : ($answer->answer === null ? 'skipped' : ($answer->answer === $question->correct_answer ? 'correct' : 'wrong'));
                                @endphp
                                <td class="prediction-cell is-{{ $status }}" title="{{ $question->question_text }}"><span>{{ $answer === null || $answer->answer === null ? '—' : ($answer->answer ? 'T' : 'N') }}</span></td>
                            @endforeach
                            <td class="text-center"><strong>{{ $prediction->points_offensive }}</strong></td>
                            @foreach($defensiveQuestions as $question)
                                @php
                                    $answer = $answers->get($question->id);
                                    $status = $answer === null || $question->correct_answer === null ? 'pending' : ($answer->answer === null ? 'skipped' : ($answer->answer === $question->correct_answer ? 'correct' : 'wrong'));
                                    if ($status === 'correct' && $answer->answer === true) {
                                        $defensivePoints++;
                                    }
                                @endphp
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
