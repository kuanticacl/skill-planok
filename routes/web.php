<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route('dashboard'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Usuarios
    Route::get('users', [UserController::class, 'index'])->middleware('can:users.view')->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->middleware('can:users.create')->name('users.create');
    Route::post('users', [UserController::class, 'store'])->middleware('can:users.create')->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('can:users.update')->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('can:users.update')->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('can:users.delete')->name('users.destroy');

    // Roles y permisos
    Route::get('roles', [RoleController::class, 'index'])->middleware('can:roles.view')->name('roles.index');
    Route::get('roles/create', [RoleController::class, 'create'])->middleware('can:roles.create')->name('roles.create');
    Route::post('roles', [RoleController::class, 'store'])->middleware('can:roles.create')->name('roles.store');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->middleware('can:roles.view')->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('can:roles.update')->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete')->name('roles.destroy');
});

require __DIR__.'/settings.php';
