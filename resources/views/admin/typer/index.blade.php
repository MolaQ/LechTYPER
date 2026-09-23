<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Typer Lecha</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="admin-shell"><div class="container-fluid"><div class="row min-vh-100">
    @include('admin.partials.sidebar')
    <main class="admin-content col-lg-10 p-3 p-md-4 p-xl-5">
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"><div><p class="eyebrow mb-2">Typer Lecha</p><h1 class="font-display h3 mb-1">Mecze sezonu {{ $season->name }}</h1><p class="text-muted-custom mb-0">W tym miejscu dodajesz mecze Lecha, pytania bonusowe i wyniki.</p></div><form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="btn btn-outline-secondary">Wyloguj</button></form></header>
        @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="bg-white border rounded-3 p-4 mb-4">
            <p class="eyebrow mb-2">Nowe typowanie</p>
            <h2 class="font-display h5 mb-3">Dodaj mecz Lecha</h2>
            <form method="POST" action="{{ route('admin.typer.matches.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Przeciwnik</label><input name="opponent" class="form-control" placeholder="np. Jagiellonia Białystok" required></div>
                    <div class="col-md-2"><label class="form-label">Lech</label><select name="lech_home" class="form-select"><option value="1">Gospodarz</option><option value="0">Gość</option></select></div>
                    <div class="col-md-3"><label class="form-label">Rozgrywki</label><select name="competition_id" class="form-select" required>@foreach($competitions as $competition)<option value="{{ $competition->id }}">{{ $competition->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Data i godzina</label><input type="datetime-local" name="scheduled_at" class="form-control" required></div>
                </div>
                <button class="btn btn-primary mt-3">Dodaj mecz</button>
            </form>
        </section>

        <section class="bg-white border rounded-3 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3"><div><p class="eyebrow mb-2">Sezon {{ $season->name }}</p><h2 class="font-display h5 mb-0">Mecze Lecha</h2></div><span class="badge text-bg-light">{{ $matches->count() }}</span></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Mecz</th><th>Rozgrywki</th><th>Termin</th><th>Status</th><th></th></tr></thead><tbody>
                @forelse($matches as $match)
                    <tr><td><strong>{{ $match->lech_home ? 'Lech Poznań - '.$match->opponent : $match->opponent.' - Lech Poznań' }}</strong></td><td>{{ $match->competition->name }}</td><td>{{ $match->scheduled_at->format('d.m.Y H:i') }}</td><td>@if($match->status === 'completed')<span class="badge text-bg-success">{{ $match->result_home }}:{{ $match->result_away }}</span>@elseif($match->status === 'cancelled')<span class="badge text-bg-secondary">Odwołany</span>@else<span class="badge text-bg-light">Zaplanowany</span>@endif</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.typer.matches.predictions', $match) }}">Otwórz mecz</a></td></tr>
                @empty
                    <tr><td colspan="5" class="text-muted-custom">Brak dodanych meczów w aktywnym sezonie.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </main>
</div></div></div>
</body>
</html>
