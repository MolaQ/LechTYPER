<!DOCTYPE html>
<html lang="pl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ config('app.name') }} | Typy - {{ $match->opponent }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body><div class="admin-shell"><div class="container-fluid"><div class="row min-vh-100">@include('admin.partials.sidebar')<main class="admin-content col-lg-10 p-3 p-md-4 p-xl-5">
<header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"><div><p class="eyebrow mb-2">{{ $match->competition->name }}{{ $match->round_number ? ' · Kolejka '.$match->round_number : '' }}</p><h1 class="font-display h3 mb-1">Typy: {{ $match->lech_home ? 'Lech Poznań - '.$match->opponent : $match->opponent.' - Lech Poznań' }}</h1><p class="text-muted-custom mb-0">{{ $match->scheduled_at->format('d.m.Y H:i') }}</p></div><a class="btn btn-outline-primary" href="{{ route('admin.typer.index') }}">Wróć do meczów</a></header>
<section class="bg-white border rounded-3 p-3 p-md-4"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="font-display h5 mb-0">Typy kibiców</h2><span class="badge text-bg-light">{{ $predictions->count() }} typów</span></div>
<div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Kibic</th><th>Wynik</th><th>Baza</th><th>Ofensywa</th><th>Obrona odjęta</th><th>Suma</th></tr></thead><tbody>
@forelse($predictions as $prediction)<tr><td>{{ $prediction->user->name }}</td><td>{{ $prediction->home_score }}:{{ $prediction->away_score }}</td><td>{{ $prediction->points_base }}</td><td>{{ $prediction->points_offensive }}</td><td>{{ $prediction->points_defensive_applied }}</td><td><strong>{{ $prediction->total_points }}</strong></td></tr>@empty<tr><td colspan="6" class="text-muted-custom">Nikt jeszcze nie wytypował tego meczu.</td></tr>@endforelse
</tbody></table></div></section>
</main></div></div></div></body></html>
