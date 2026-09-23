<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Terminarz</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="admin-shell"><div class="container-fluid"><div class="row min-vh-100">
    @include('admin.partials.sidebar')
    <main class="admin-content col-lg-10 p-3 p-md-4 p-xl-5">
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"><div><p class="eyebrow mb-2">Rozgrywki</p><h1 class="font-display h3 mb-1">Terminarz</h1><p class="text-muted-custom mb-0">Mecze i kolejki sezonu.</p></div><div class="d-flex gap-2"><a class="btn btn-primary" href="{{ route('admin.typer.index') }}">Wyniki i odpowiedzi bonusowe</a><form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="btn btn-outline-secondary">Wyloguj</button></form></div></header>
        @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="bg-white border rounded-3 p-3 p-md-4 mb-4"><form method="GET" action="{{ route('admin.schedule.index') }}"><label class="form-label">Sezon</label><select class="form-select" name="season_id" onchange="this.form.submit()">@foreach($seasons as $availableSeason)<option value="{{ $availableSeason->id }}" @selected($availableSeason->id === $season->id)>{{ $availableSeason->name }} ({{ $availableSeason->status }})</option>@endforeach</select></form></section>
    </main>
</div></div></div>
</body>
</html>
