<div class="typer-panel">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    @if($match === null)
        <div class="bg-white border rounded-3 p-4"><p class="text-muted-custom mb-0">Brak otwartego typowania.</p></div>
    @else
        <div class="bg-white border rounded-3 p-4 mb-4">
            <p class="eyebrow mb-2">{{ $match->competition->name }}</p>
            <h2 class="font-display h4 mb-1">Lech Poznań {{ $match->lech_home ? '-' : '@' }} {{ $match->opponent }}</h2>
            <p class="text-muted-custom mb-0">{{ $match->scheduled_at->format('d.m.Y H:i') }}</p>
        </div>

        <div class="bg-white border rounded-3 p-4 mb-4">
            <p class="eyebrow mb-2">Wynik po 90 minutach</p>
            <div class="d-flex justify-content-center align-items-center gap-3">
                <div class="text-center"><strong class="d-block mb-2">Lech</strong><button type="button" class="btn btn-outline-primary" wire:click="decrement('homeScore')">-</button><span class="display-6 mx-3">{{ $homeScore }}</span><button type="button" class="btn btn-outline-primary" wire:click="increment('homeScore')">+</button></div>
                <span class="display-6">:</span>
                <div class="text-center"><strong class="d-block mb-2">{{ $match->opponent }}</strong><button type="button" class="btn btn-outline-primary" wire:click="decrement('awayScore')">-</button><span class="display-6 mx-3">{{ $awayScore }}</span><button type="button" class="btn btn-outline-primary" wire:click="increment('awayScore')">+</button></div>
            </div>
        </div>

        @foreach(['offensive' => 'Bonus ofensywny', 'defensive' => 'Bonus defensywny'] as $type => $title)
            <div class="bg-white border rounded-3 p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><p class="eyebrow mb-2">{{ $title }}</p><h3 class="font-display h5 mb-0">Odpowiedzi TAK / NIE</h3></div><span class="badge text-bg-light">{{ $type === 'offensive' ? $this->offensiveAnsweredCount() : $this->defensiveAnsweredCount() }} / 5 odpowiedzi</span></div>
                <div class="d-grid gap-3">
                    @foreach($match->bonusQuestions->where('type', $type) as $question)
                        <div class="border rounded-3 p-3"><div class="mb-2">{{ $question->question_text }}</div><div class="btn-group" role="group"><button type="button" class="btn {{ ($answers[$question->id] ?? null) === true ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="toggleAnswer({{ $question->id }}, true)">TAK</button><button type="button" class="btn {{ ($answers[$question->id] ?? null) === false ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="toggleAnswer({{ $question->id }}, false)">NIE</button><button type="button" class="btn {{ ($answers[$question->id] ?? null) === null ? 'btn-secondary' : 'btn-outline-secondary' }}" wire:click="toggleAnswer({{ $question->id }}, null)">Wyczyść</button></div></div>
                    @endforeach
                </div>
                <p class="small text-muted-custom mt-3 mb-0">Jedna błędna odpowiedź zeruje cały bonus. Puste odpowiedzi nie są błędem.</p>
            </div>
        @endforeach

        <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">Zapisz typ</button>

        @if($opponentPrediction !== null)
            <div class="bg-white border rounded-3 p-4 mt-4">
                <p class="eyebrow mb-2">Starcie H2H</p>
                <h3 class="font-display h5 mb-3">Typ rywala: {{ $opponentPrediction['name'] }}</h3>
                @if($opponentPrediction['locked'])
                    <p class="text-muted-custom mb-0">Typ rywala odsłoni się po rozpoczęciu meczu.</p>
                @elseif($opponentPrediction['prediction'] === null)
                    <p class="text-muted-custom mb-0">Rywal nie zapisał typu na ten mecz.</p>
                @else
                    <p class="mb-0"><strong>{{ $opponentPrediction['prediction']->home_score }}:{{ $opponentPrediction['prediction']->away_score }}</strong></p>
                @endif
            </div>
        @endif
    @endif
</div>
