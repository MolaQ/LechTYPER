<aside class="admin-right-sidebar" aria-label="Nawigacja panelu administracyjnego">
    <div class="sidebar-label mb-2">Panel zarządzania</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }} px-3 py-2" href="{{ route('admin.dashboard') }}">▦ <span class="ms-2">Dashboard</span></a>
        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }} px-3 py-2" href="{{ route('admin.users.index') }}">♧ <span class="ms-2">Użytkownicy</span></a>
        <a class="nav-link {{ request()->routeIs('admin.leagues.*') ? 'active' : '' }} px-3 py-2" href="{{ route('admin.leagues.index') }}">⚽ <span class="ms-2">Ligi</span></a>
        <a class="nav-link px-3 py-2" href="{{ route('home') }}">← <span class="ms-2">Wróć do witryny</span></a>
    </nav>
</aside>
