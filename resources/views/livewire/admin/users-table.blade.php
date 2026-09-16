<div id="users-table">
    @if (session('status'))
        <div class="alert alert-success py-2">{{ session('status') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" wire:click="openCreate">＋ Dodaj użytkownika</button></div>
        <div class="col-lg-6">
            <label class="form-label small" for="user-search">Szukaj użytkownika</label>
            <input id="user-search" type="search" class="form-control" placeholder="Imię, email lub nazwa X..." wire:model.live.debounce.300ms="search">
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label small" for="user-role">Rola</label>
            <select id="user-role" class="form-select" wire:model.live="role">
                <option value="">Wszystkie</option>
                <option value="superadmin">Superadmin</option>
                <option value="admin">Admin</option>
                <option value="moderator">Moderator</option>
                <option value="kibol">Kibol</option>
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label small" for="user-premium">Status</label>
            <select id="user-premium" class="form-select" wire:model.live="premium">
                <option value="">Wszyscy</option>
                <option value="active">Aktywne Premium</option>
                <option value="standard">Standard</option>
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label small" for="premium-days">Nadaj Premium</label>
            <select id="premium-days" class="form-select" wire:model="premiumDays">
                <option value="7">7 dni</option>
                <option value="30">30 dni</option>
            </select>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr><th>Użytkownik</th><th>Email</th><th>Nazwa X</th><th>Rola</th><th>Ostatnie logowanie</th><th>Premium</th><th>Akcje</th></tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td><button class="btn btn-link p-0 text-start" wire:click="viewUser({{ $user->id }})"><strong>{{ $user->name }}</strong></button></td>
                        <td>{{ $user->email }}</td>
                        <td>@if (in_array(auth()->user()->role, ['superadmin', 'admin'], true))<form method="POST" action="{{ route('admin.users.x-username.update', $user) }}" class="d-flex gap-1">@csrf @method('PUT')<input class="form-control form-control-sm" name="x_username" value="{{ $user->x_username }}" placeholder="brak"><button class="btn btn-sm btn-outline-secondary">Zapisz</button></form>@else{{ $user->x_username ?? 'brak' }}@endif</td>
                        <td><span class="badge rounded-pill role-pill">{{ ucfirst($user->role) }}</span></td>
                        <td>{{ $user->last_login_at?->format('d.m.Y H:i') ?? 'Jeszcze się nie logował' }}</td>
                        <td>
                            @if ($user->hasActivePremium())
                                <span class="badge rounded-pill text-bg-warning status-pill">Do {{ $user->premium_until?->format('d.m.Y') ?? 'bezterminowo' }}</span>
                            @else
                                <span class="text-muted-custom small">Standard</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary me-1" wire:click="viewUser({{ $user->id }})">Podgląd</button>
                            @if (in_array(auth()->user()->role, ['superadmin', 'admin'], true))<button class="btn btn-sm btn-outline-primary" wire:click="editUser({{ $user->id }})">Edytuj</button>@endif
                            @if ($user->last_login_at && ! $user->hasActivePremium() && in_array(auth()->user()->role, ['superadmin', 'admin'], true))<button class="btn btn-sm btn-outline-warning" wire:click="preparePremium({{ $user->id }})">Premium</button>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted-custom py-4">Brak użytkowników dla wybranych filtrów.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}

    <div class="modal fade" id="createUserModal" tabindex="-1" wire:ignore.self><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Dodaj użytkownika</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form wire:submit="createUser"><div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Imię i nazwisko</label><input class="form-control" wire:model="name" required></div><div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" wire:model="email" required></div><div class="col-md-6"><label class="form-label">Nazwa konta X</label><input class="form-control" wire:model="xUsername" required></div><div class="col-md-6"><label class="form-label">Rola</label><select class="form-select" wire:model="newRole"><option value="kibol">Kibol</option><option value="moderator">Moderator</option>@if (auth()->user()->role === 'superadmin')<option value="admin">Admin</option><option value="superadmin">Superadmin</option>@endif</select></div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Anuluj</button><button class="btn btn-primary">Utwórz konto</button></div></form></div></div></div>

    <div class="modal fade" id="viewUserModal" tabindex="-1" wire:ignore.self><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Szczegóły użytkownika</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">@if ($viewingUser)<dl class="row mb-0"><dt class="col-sm-5">Imię</dt><dd class="col-sm-7">{{ $viewingUser->name }}</dd><dt class="col-sm-5">Email</dt><dd class="col-sm-7">{{ $viewingUser->email }}</dd><dt class="col-sm-5">Nazwa X</dt><dd class="col-sm-7">{{ $viewingUser->x_username ?? 'Nie ustawiono' }}</dd><dt class="col-sm-5">Rola</dt><dd class="col-sm-7">{{ ucfirst($viewingUser->role) }}</dd><dt class="col-sm-5">Ostatnie logowanie</dt><dd class="col-sm-7">{{ $viewingUser->last_login_at?->format('d.m.Y H:i') ?? 'Brak' }}</dd><dt class="col-sm-5">Premium</dt><dd class="col-sm-7">@if ($viewingUser->hasActivePremium())<span class="badge text-bg-warning">Aktywne do {{ $viewingUser->premium_until?->format('d.m.Y') ?? 'bezterminowo' }}</span>@else<span class="text-muted-custom">Nieaktywne</span>@endif</dd></dl>@if ($viewingUser->temporary_password)<div class="temporary-password-panel mt-4"><div class="small text-uppercase fw-bold">Hasło tymczasowe</div><code>{{ $viewingUser->temporary_password }}</code><p class="small mb-0 mt-2">Widoczne do pierwszego logowania użytkownika.</p></div>@endif@endif</div><div class="modal-footer">@if ($viewingUser && in_array(auth()->user()->role, ['superadmin', 'admin'], true))<button class="btn btn-outline-warning" wire:click="resetPassword({{ $viewingUser->id }})">Ustaw nowe hasło tymczasowe</button>@endif<button type="button" class="btn btn-primary" data-bs-dismiss="modal">Zamknij</button></div></div></div></div>

    <div class="modal fade" id="editUserModal" tabindex="-1" wire:ignore.self><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edytuj użytkownika</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form wire:submit="saveUser"><div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Imię i nazwisko</label><input class="form-control" wire:model="name" required></div><div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" wire:model="email" required></div><div class="col-md-6"><label class="form-label">Nazwa konta X</label><input class="form-control" wire:model="xUsername" required></div><div class="col-md-6"><label class="form-label">Rola</label><select class="form-select" wire:model="newRole"><option value="kibol">Kibol</option><option value="moderator">Moderator</option>@if (auth()->user()->role === 'superadmin')<option value="admin">Admin</option><option value="superadmin">Superadmin</option>@endif</select></div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Anuluj</button><button class="btn btn-primary">Zapisz zmiany</button></div></form></div></div></div>

    <div class="modal fade" id="premiumModal" tabindex="-1" wire:ignore.self><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Nadaj Premium</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="text-muted-custom">Premium można nadać tylko użytkownikowi, który przynajmniej raz się zalogował.</p><label class="form-label">Okres dostępu</label><select class="form-select" wire:model="premiumDays"><option value="7">7 dni</option><option value="30">30 dni</option></select></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Anuluj</button>@if ($premiumUserId)<button class="btn btn-warning" wire:click="grantPremium({{ $premiumUserId }})" data-bs-dismiss="modal">Nadaj Premium</button>@endif</div></div></div></div>
</div>
