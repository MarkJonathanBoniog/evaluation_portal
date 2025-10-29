<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/debug', function () {
    $admin = User::where('email', 'sys@example.com')->first();
    return [
        'roles' => \Spatie\Permission\Models\Role::pluck('name')->all(),
        'sys_admin_roles' => $admin?->getRoleNames()->all(),
        'can_manage_users' => $admin?->can('users.manage'),
    ];
});

require __DIR__.'/auth.php';
require __DIR__.'/main.php';
