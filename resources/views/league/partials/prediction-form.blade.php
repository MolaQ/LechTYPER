@php
    $leagueRound = $match->leagueRound;
    $bonusQuestions = $leagueRound?->bonusQuestions ?? collect();
    $offensiveQuestions = $bonusQuestions->where('type', 'offensive');
    $defensiveQuestions = $bonusQuestions->where('type', 'defensive');
    $realHomeLabel = $leagueRound?->real_home_team ?: 'Gospodarz (do ustalenia)';
    $realAwayLabel = $leagueRound?->real_away_team ?: 'Gość (do ustalenia)';
@endphp
<div class="alert {{ $leagueRound?->real_home_team ? 'alert-info' : 'alert-warning' }} mb-3">
    @if($leagueRound?->real_home_team)
        Mecz Lecha przypisany do tej kolejki: <strong>{{ $leagueRound->real_home_team }} - {{ $leagueRound->real_away_team }}</strong>{{ $leagueRound->competition ? ' · '.$leagueRound->competition : '' }}
    @else
        Administrator nie przypisał jeszcze meczu Lecha do tej kolejki. Typujesz wynik meczu, który zostanie ogłoszony później.
    @endif
</div>
<form method="POST" action="{{ route('league.selection.store', $match) }}">
    @csrf
    <div class="row g-3 mb-3">
        <div class="col-6">
            <label class="form-label">{{ $realHomeLabel }}</label>
            <input type="number" class="form-control" name="home_score" min="0" max="20" value="{{ $selection->home_score ?? 0 }}" required>
        </div>
        <div class="col-6">
            <label class="form-label">{{ $realAwayLabel }}</label>
            <input type="number" class="form-control" name="away_score" min="0" max="20" value="{{ $selection->away_score ?? 0 }}" required>
        </div>
    </div>

    @if($bonusQuestions->isEmpty())
        <p class="text-muted-custom small mb-3">Brak pytań bonusowych dla tej kolejki.</p>
    @endif

    @foreach(['offensive' => ['Bonus ofensywny', $offensiveQuestions], 'defensive' => ['Bonus defensywny', $defensiveQuestions]] as [$title, $questions])
        @if($questions->isNotEmpty())
            <p class="eyebrow mb-2 mt-3">{{ $title }}</p>
            <div class="d-grid gap-2 mb-3">
                @foreach($questions as $question)
                    @php $currentAnswer = $selection?->answers->firstWhere('league_round_bonus_question_id', $question->id)?->answer; @endphp
                    <div class="border rounded-3 p-2">
                        <div class="small mb-2">{{ $question->question_text }}</div>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="answers[{{ $question->id }}]" id="answer-{{ $question->id }}-yes" value="1" @checked($currentAnswer === true)>
                            <label class="btn btn-outline-primary" for="answer-{{ $question->id }}-yes">TAK</label>
                            <input type="radio" class="btn-check" name="answers[{{ $question->id }}]" id="answer-{{ $question->id }}-no" value="0" @checked($currentAnswer === false)>
                            <label class="btn btn-outline-primary" for="answer-{{ $question->id }}-no">NIE</label>
                            <input type="radio" class="btn-check" name="answers[{{ $question->id }}]" id="answer-{{ $question->id }}-empty" value="" @checked($currentAnswer === null)>
                            <label class="btn btn-outline-secondary" for="answer-{{ $question->id }}-empty">Wyczyść</label>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="small text-muted-custom mb-0">Jedna błędna odpowiedź zeruje cały bonus {{ $title === 'Bonus ofensywny' ? 'ofensywny' : 'defensywny' }}. Puste odpowiedzi nie są błędem.</p>
        @endif
    @endforeach

    <button class="btn btn-primary mt-3" type="submit">Zapisz typ</button>
</form>
