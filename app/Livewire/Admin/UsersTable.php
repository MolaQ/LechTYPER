<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class UsersTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $role = '';

    public string $premium = '';

    public int $premiumDays = 30;

    public bool $showCreateModal = false;

    public ?int $viewingUserId = null;

    public ?int $editingUserId = null;

    public ?int $premiumUserId = null;

    public string $name = '';

    public string $email = '';

    public string $xUsername = '';

    public string $newRole = 'kibol';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedPremium(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'email', 'xUsername', 'viewingUserId', 'editingUserId', 'premiumUserId']);
        $this->newRole = 'kibol';
        $this->showCreateModal = true;
        $this->dispatch('show-user-modal', modal: 'createUserModal');
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->viewingUserId = null;
        $this->editingUserId = null;
    }

    public function createUser(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'xUsername' => ['required', 'string', 'max:80'],
            'newRole' => ['required', 'in:superadmin,admin,moderator,kibol'],
        ]);

        if (in_array($data['newRole'], ['superadmin', 'admin'], true)) {
            abort_unless(Auth::user()?->role === 'superadmin', 403);
        }

        $temporaryPassword = Str::password(12, true, true, false, false);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'x_username' => $data['xUsername'],
            'password' => $temporaryPassword,
            'temporary_password' => $temporaryPassword,
            'role' => $data['newRole'],
            'must_change_password' => true,
        ]);

        Mail::raw("Witaj {$user->name},\n\nTwoje konto w ".config('app.name')." jest gotowe.\nEmail: {$user->email}\nHasło tymczasowe: {$temporaryPassword}\n\nZmień hasło przy pierwszym logowaniu.", function ($message) use ($user): void {
            $message->to($user->email)->subject(config('app.name').' - dane logowania');
        });

        $this->reset(['name', 'email', 'xUsername']);
        $this->showCreateModal = false;
        session()->flash('status', "Konto {$user->email} zostało utworzone.");
    }

    public function viewUser(int $userId): void
    {
        $this->viewingUserId = $userId;
        $this->dispatch('show-user-modal', modal: 'viewUserModal');
    }

    public function preparePremium(int $userId): void
    {
        abort_unless(in_array(Auth::user()?->role, ['superadmin', 'admin'], true), 403);
        $user = User::findOrFail($userId);
        if ($user->last_login_at === null) {
            session()->flash('status', 'Premium można nadać dopiero po pierwszym logowaniu użytkownika.');

            return;
        }
        $this->premiumUserId = $userId;
        $this->dispatch('show-user-modal', modal: 'premiumModal');
    }

    public function editUser(int $userId): void
    {
        abort_unless(in_array(Auth::user()?->role, ['superadmin', 'admin'], true), 403);
        $user = User::findOrFail($userId);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->xUsername = $user->x_username ?? '';
        $this->newRole = $user->role;
        $this->dispatch('show-user-modal', modal: 'editUserModal');
    }

    public function saveUser(): void
    {
        abort_unless(in_array(Auth::user()?->role, ['superadmin', 'admin'], true), 403);
        $user = User::findOrFail($this->editingUserId);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'xUsername' => ['required', 'string', 'max:80'],
            'newRole' => ['required', 'in:superadmin,admin,moderator,kibol'],
        ]);
        if (in_array($data['newRole'], ['superadmin', 'admin'], true)) {
            abort_unless(Auth::user()?->role === 'superadmin', 403);
        }
        $user->update(['name' => $data['name'], 'email' => $data['email'], 'x_username' => $data['xUsername'], 'role' => $data['newRole']]);
        $this->editingUserId = null;
        session()->flash('status', "Dane użytkownika {$user->email} zostały zapisane.");
    }

    public function grantPremium(int $userId): void
    {
        abort_unless(in_array(Auth::user()?->role, ['superadmin', 'admin'], true), 403);
        $this->validate(['premiumDays' => ['required', 'integer', 'in:7,30']]);
        $user = User::findOrFail($userId);
        if ($user->last_login_at === null) {
            session()->flash('status', 'Premium można nadać dopiero po pierwszym logowaniu użytkownika.');

            return;
        }
        $user->update(['is_premium' => true, 'premium_until' => now()->addDays($this->premiumDays), 'premium_source' => 'manual']);
        session()->flash('status', "Premium nadane użytkownikowi {$user->email}.");
    }

    public function resetPassword(int $userId): void
    {
        abort_unless(in_array(Auth::user()?->role, ['superadmin', 'admin'], true), 403);

        $user = User::findOrFail($userId);
        $temporaryPassword = Str::password(12, true, true, false, false);
        $user->update([
            'password' => $temporaryPassword,
            'temporary_password' => $temporaryPassword,
            'must_change_password' => true,
        ]);

        Mail::raw("Witaj {$user->name},\n\nAdministrator ustawił nowe hasło tymczasowe do Twojego konta w ".config('app.name').".\nHasło tymczasowe: {$temporaryPassword}\n\nZmień hasło przy pierwszym logowaniu.", function ($message) use ($user): void {
            $message->to($user->email)->subject(config('app.name').' - nowe hasło tymczasowe');
        });

        $this->viewingUserId = $user->id;
        session()->flash('status', "Ustawiono nowe hasło tymczasowe dla {$user->email}.");
        $this->dispatch('show-user-modal', modal: 'viewUserModal');
    }

    public function render(): View
    {
        $users = User::query()
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', "%{$this->search}%")->orWhere('x_username', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->role !== '', fn ($query) => $query->where('role', $this->role))
            ->when($this->premium === 'active', fn ($query) => $query->where('is_premium', true)->where(fn ($query) => $query->whereNull('premium_until')->orWhereDate('premium_until', '>=', today())))
            ->when($this->premium === 'standard', fn ($query) => $query->where(fn ($query) => $query->where('is_premium', false)->orWhereDate('premium_until', '<', today())))
            ->latest()->paginate(15);

        return view('livewire.admin.users-table', ['users' => $users, 'viewingUser' => $this->viewingUserId ? User::find($this->viewingUserId) : null]);
    }
}
