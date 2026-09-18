<aside class="admin-sidebar col-lg-2 p-4 d-flex flex-column">
    <a class="brand d-flex align-items-center gap-2 mb-5" href="{{ route('home') }}"><span class="brand-mark">LP</span><span>#LechTYPER</span></a>
    <div class="sidebar-label mb-2">Panel zarządzania</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }} px-3 py-2" href="{{ route('admin.dashboard') }}">▦ <span class="ms-2">Dashboard</span></a>
        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }} px-3 py-2" href="{{ route('admin.users.index') }}">♧ <span class="ms-2">Użytkownicy</span></a>
        <a class="nav-link {{ request()->routeIs('admin.leagues.*') ? 'active' : '' }} px-3 py-2" href="{{ route('admin.leagues.index') }}">⚽ <span class="ms-2">Ligi</span></a>
        <a class="nav-link {{ request()->routeIs('admin.schedule.*') ? 'active' : '' }} px-3 py-2" href="{{ route('admin.schedule.index') }}">◷ <span class="ms-2">Terminarz</span></a>
        <a class="nav-link px-3 py-2" href="#mecze">◷ <span class="ms-2">Mecze i typy</span></a>
        <a class="nav-link px-3 py-2" href="#wpisy">▤ <span class="ms-2">Moderacja wpisów</span></a>
    </nav>
    <div class="mt-auto pt-5"><a class="nav-link px-3 py-2" href="{{ route('home') }}">← <span class="ms-2">Wróć do witryny</span></a></div>
</aside>
