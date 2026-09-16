<?php

use App\Http\Controllers\AdminLeagueController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LeagueController::class, 'home'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login/admin', fn () => redirect()->route('login'));
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/haslo/przypomnij', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/haslo/przypomnij', [AuthController::class, 'sendPasswordResetLink'])->name('password.email');
Route::get('/haslo/reset/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/haslo/reset', [AuthController::class, 'resetPassword'])->name('password.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
Route::view('/admin', 'admin')->name('admin.dashboard')->middleware(['auth', 'admin']);
Route::middleware('auth')->group(function (): void {
    Route::get('/liga', fn () => redirect()->to(route('home').'#liga'))->name('league.index');
    Route::post('/liga/mecze/{match}/typ', [LeagueController::class, 'submitSelection'])->name('league.selection.store');
    Route::post('/admin/liga/mecze/{match}/rozlicz', [AdminLeagueController::class, 'completeMatch'])->name('admin.league.matches.complete')->middleware('admin');
    Route::get('/haslo/zmien', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/haslo/zmien', [AuthController::class, 'changePassword'])->name('password.update');
    Route::view('/profil', 'profile')->name('profile');
    Route::view('/premium', 'premium')->name('premium');
    Route::post('/admin/uzytkownicy', [AuthController::class, 'createUser'])->name('admin.users.store')->middleware('admin');
    Route::put('/admin/uzytkownicy/{user}/nazwa-x', [AuthController::class, 'updateXUsername'])->name('admin.users.x-username.update')->middleware('admin');
    Route::view('/admin/uzytkownicy', 'admin.users.index')->name('admin.users.index')->middleware('admin');
    Route::get('/admin/uzytkownicy/dodaj', fn () => redirect()->route('admin.users.index'))->name('admin.users.create')->middleware('admin');
    Route::view('/statystyki', 'stats')->name('stats')->middleware('premium');
    Route::get('/premium/kup/{days}', [PaymentController::class, 'create'])->name('payment.create');
    Route::post('/admin/uzytkownicy/admin', [AuthController::class, 'createUser'])->name('admin.users.admin.store')->middleware('superadmin');
});
Route::post('/platnosci/przelewy24/notify', [PaymentController::class, 'notify'])->name('payment.notify');
Route::get('/platnosci/przelewy24/powrot', [PaymentController::class, 'return'])->name('payment.return');
