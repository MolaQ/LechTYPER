<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Premium</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="container py-5"><a class="brand d-flex align-items-center gap-2 mb-5" href="{{ route('home') }}"><span class="brand-mark">LP</span><span>#LechTYPER</span></a><div class="row justify-content-center"><div class="col-lg-8 text-center"><p class="eyebrow mb-2">Rozszerz swoje typowanie</p><h1 class="font-display h2 mb-3">Odblokuj statystyki Premium</h1><p class="text-muted-custom mb-5">Wybierz dostęp na 7 albo 30 dni. Po opłaceniu dostęp zostanie aktywowany po potwierdzeniu Przelewy24.</p><div class="row g-3 text-start"><div class="col-md-6"><div class="bg-white border rounded-3 p-4 h-100"><p class="eyebrow">Krótki dostęp</p><h2 class="font-display h4">7 dni</h2><p class="text-muted-custom small">Statystyki i analiza typów przez tydzień.</p><a class="btn btn-outline-primary w-100" href="{{ route('payment.create', 7) }}">Kup 7 dni</a></div></div><div class="col-md-6"><div class="bg-white border rounded-3 p-4 h-100"><p class="eyebrow">Najczęściej wybierany</p><h2 class="font-display h4">30 dni</h2><p class="text-muted-custom small">Pełny miesiąc dostępu do statystyk Premium.</p><a class="btn btn-primary w-100" href="{{ route('payment.create', 30) }}">Kup 30 dni</a></div></div></div></div></div></main>
</body>
</html>
