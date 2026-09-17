<nav class="admin-quick-nav" aria-label="Nawigacja panelu administracyjnego">
    <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
    <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Użytkownicy</a>
    <a class="{{ request()->routeIs('admin.leagues.*') ? 'active' : '' }}" href="{{ route('admin.leagues.index') }}">Ligi</a>
    <a href="{{ route('home') }}">Wróć do witryny</a>
</nav>
