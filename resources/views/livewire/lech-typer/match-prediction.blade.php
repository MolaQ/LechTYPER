<div class="typer-panel">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="content-grid">
        <div class="main-column">
            <div class="bg-white border rounded-3 p-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <p class="eyebrow mb-2">Mecze dostępne do typowania</p>
                        <h2 class="font-display h4 mb-0">Wybierz spotkanie</h2>
                    </div>
                    <span class="badge text-bg-light">{{ $matches->count() }}</span>
                </div>

                <div class="d-grid gap-3">
                    @forelse($matches as $availableMatch)
                        @php($typingOpen = $availableMatch->scheduled_at->isFuture())
                        <div class="border rounded-3 p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <p class="eyebrow mb-1">{{ $availableMatch->competition->name }}{{ $availableMatch->round_number ? ' · Kolejka '.$availableMatch->round_number : '' }}</p>
                                <h3 class="font-display h5 mb-1">{{ $availableMatch->lech_home ? 'Lech Poznań - '.$availableMatch->opponent : $availableMatch->opponent.' - Lech Poznań' }}</h3>
                                <p class="text-muted-custom small mb-0">{{ $availableMatch->scheduled_at->format('d.m.Y, H:i') }}</p>
                            </div>
                            @if($typingOpen)
                                <button type="button" class="btn btn-primary" wire:click="selectMatch({{ $availableMatch->id }})">{{ $availableMatch->id === $match?->id ? 'Edytuj typ' : 'Typuj mecz' }}</button>
                            @else
                                <span class="badge text-bg-secondary">Typowanie zamknięte</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted-custom mb-0">Brak meczów dostępnych do typowania.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <aside class="secondary-column">
            <div class="league-panel p-4 mb-4">
                <p class="eyebrow mb-2">Typer Lecha</p>
                <h2 class="font-display h5 mb-2">Typuj każdy dostępny mecz</h2>
                <p class="text-muted-custom small mb-0">Możesz edytować wynik i odpowiedzi bonusowe do momentu rozpoczęcia spotkania.</p>
            </div>
            <div class="league-panel p-4 mb-4">
                <p class="eyebrow mb-3">Jak liczymy punkty</p>
                <ul class="list-unstyled small mb-0">
                    <li class="d-flex justify-content-between border-bottom pb-2 mb-2"><span>Dokładny wynik</span><strong>3 pkt</strong></li>
                    <li class="d-flex justify-content-between border-bottom pb-2 mb-2"><span>Różnica bramek</span><strong>2 pkt</strong></li>
                    <li class="d-flex justify-content-between"><span>Poprawny rezultat</span><strong>1 pkt</strong></li>
                </ul>
            </div>
            <div class="mini-panel">
                <p class="eyebrow mb-2">Liga kiboli</p>
                <p class="text-muted-custom small mb-3">Sprawdź tabelę i rywalizację w swojej lidze.</p>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('league.index') }}">Otwórz ligę</a>
            </div>
        </aside>
    </div>

    @if($match !== null)
        <div class="modal-backdrop fade show"></div>
        <div class="modal d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="prediction-modal-title">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow mb-1">{{ $match->competition->name }}</p>
                            <h2 class="modal-title font-display h4" id="prediction-modal-title">{{ $match->lech_home ? 'Lech Poznań - '.$match->opponent : $match->opponent.' - Lech Poznań' }}</h2>
                            <p class="text-muted-custom small mb-0">{{ $match->scheduled_at->format('d.m.Y H:i') }}</p>
                        </div>
                        <button type="button" class="btn-close" aria-label="Zamknij" wire:click="closeMatch"></button>
                    </div>
                    <div class="modal-body">
                        @if($match->scheduled_at->isFuture())
                            <div class="bg-white border rounded-3 p-4 mb-4">
                                <p class="eyebrow mb-2">Wynik po 90 minutach</p>
                                <div class="d-flex justify-content-center align-items-center gap-3">
                                    <div class="text-center"><strong class="d-block mb-2">{{ $match->lech_home ? 'Lech' : $match->opponent }}</strong><button type="button" class="btn btn-outline-primary" wire:click="decrement('homeScore')">-</button><span class="display-6 mx-3">{{ $homeScore }}</span><button type="button" class="btn btn-outline-primary" wire:click="increment('homeScore')">+</button></div>
                                    <span class="display-6">:</span>
                                    <div class="text-center"><strong class="d-block mb-2">{{ $match->lech_home ? $match->opponent : 'Lech' }}</strong><button type="button" class="btn btn-outline-primary" wire:click="decrement('awayScore')">-</button><span class="display-6 mx-3">{{ $awayScore }}</span><button type="button" class="btn btn-outline-primary" wire:click="increment('awayScore')">+</button></div>
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
                        @else
                            <div class="alert alert-secondary mb-0">Typowanie zostało zamknięte, ponieważ mecz już się rozpoczął.</div>
                            @if($opponentPrediction !== null && $opponentPrediction['prediction'] !== null)
                                <div class="bg-white border rounded-3 p-4 mt-4">
                                    <p class="eyebrow mb-2">Starcie H2H</p>
                                    <h3 class="font-display h5 mb-3">Typ rywala: {{ $opponentPrediction['name'] }}</h3>
                                    <p class="mb-0"><strong>{{ $opponentPrediction['prediction']->home_score }}:{{ $opponentPrediction['prediction']->away_score }}</strong></p>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
