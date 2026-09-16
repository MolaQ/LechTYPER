<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $adminEmail = (string) config('auth.admin.email');
        $adminPassword = config('auth.admin.password');

        if ($credentials['email'] === $adminEmail
            && is_string($adminPassword)
            && $adminPassword !== ''
            && hash_equals($adminPassword, $credentials['password'])) {
            User::updateOrCreate(
                ['email' => $adminEmail],
                ['name' => 'Administrator', 'password' => $adminPassword, 'role' => 'superadmin', 'must_change_password' => false],
            );
        }

        if (! Auth::attempt($credentials, true)) {
            return back()->withErrors(['email' => 'Nieprawidłowy email lub hasło.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now(), 'temporary_password' => null])->save();

        return $request->user()->must_change_password
            ? redirect()->route('password.change')
            : redirect()->intended(in_array($request->user()->role, ['superadmin', 'admin', 'moderator'], true) ? route('admin.dashboard') : route('league.index'));
    }

    public function createUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'x_username' => ['required', 'string', 'max:80'],
            'role' => ['required', 'in:superadmin,admin,moderator,kibol'],
        ]);

        if (in_array($data['role'], ['superadmin', 'admin'], true) && $request->user()->role !== 'superadmin') {
            abort(403, 'Tylko superadmin może tworzyć konta administracyjne.');
        }
        $temporaryPassword = Str::password(12, true, true, false, false);
        $user = User::create([
            'name' => $data['name'],
            'x_username' => $data['x_username'] ?? null,
            'email' => $data['email'],
            'password' => $temporaryPassword,
            'temporary_password' => $temporaryPassword,
            'role' => $data['role'],
            'must_change_password' => true,
        ]);

        Mail::raw("Witaj {$user->name},\n\nTwoje konto w ".config('app.name')." jest gotowe.\nEmail: {$user->email}\nHasło tymczasowe: {$temporaryPassword}\n\nZmień hasło przy pierwszym logowaniu.", function ($message) use ($user): void {
            $message->to($user->email)->subject(config('app.name').' - dane logowania');
        });

        return redirect()->route('admin.users.index')->with('status', "Konto {$user->email} zostało utworzone. Dane logowania wysłano na email.");
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($data);

        return back()->with('status', 'Jeśli konto istnieje, wysłaliśmy link do ustawienia nowego hasła.');
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->string('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'string', 'min:12', 'confirmed']]);
        $status = Password::reset($data, function (User $user, string $password): void {
            $user->forceFill(['password' => $password, 'must_change_password' => false])->save();
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Hasło zostało ustawione. Możesz się zalogować.')
            : back()->withErrors(['email' => __($status)])->withInput(['email' => $data['email']]);
    }

    public function updateXUsername(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($request->user()?->role, ['superadmin', 'admin'], true), 403);
        $data = $request->validate(['x_username' => ['nullable', 'string', 'max:80']]);
        $user->update(['x_username' => $data['x_username'] ?: null]);

        return back()->with('status', "Nazwa X użytkownika {$user->email} została zaktualizowana.");
    }

    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:12', 'confirmed']]);
        $request->user()->update(['password' => $data['password'], 'must_change_password' => false]);

        return redirect()->intended(in_array($request->user()->role, ['superadmin', 'admin', 'moderator'], true) ? route('admin.dashboard') : route('home'))->with('status', 'Hasło zostało zmienione.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
