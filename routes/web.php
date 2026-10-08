<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\LeadFieldController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\StageController;
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

    // Clientes
    Route::get('clients', [ClientController::class, 'index'])->middleware('can:clients.view')->name('clients.index');
    Route::get('clients/create', [ClientController::class, 'create'])->middleware('can:clients.create')->name('clients.create');
    Route::post('clients', [ClientController::class, 'store'])->middleware('can:clients.create')->name('clients.store');
    Route::get('clients/{client}', [ClientController::class, 'show'])->middleware('can:clients.view')->name('clients.show');
    Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->middleware('can:clients.update')->name('clients.edit');
    Route::put('clients/{client}', [ClientController::class, 'update'])->middleware('can:clients.update')->name('clients.update');
    Route::delete('clients/{client}', [ClientController::class, 'destroy'])->middleware('can:clients.delete')->name('clients.destroy');

    // Configuración del CRM: orígenes (con API key), etapas del Kanban y campos personalizados
    Route::prefix('crm')->group(function () {
        Route::middleware('can:sources.manage')->group(function () {
            Route::get('sources', [SourceController::class, 'index'])->name('sources.index');
            Route::post('sources', [SourceController::class, 'store'])->name('sources.store');
            Route::put('sources/{source}', [SourceController::class, 'update'])->name('sources.update');
            Route::post('sources/{source}/regenerate-key', [SourceController::class, 'regenerateKey'])->name('sources.regenerate-key');
            Route::delete('sources/{source}', [SourceController::class, 'destroy'])->name('sources.destroy');
        });

        Route::middleware('can:stages.manage')->group(function () {
            Route::get('stages', [StageController::class, 'index'])->name('stages.index');
            Route::post('stages', [StageController::class, 'store'])->name('stages.store');
            Route::put('stages/reorder', [StageController::class, 'reorder'])->name('stages.reorder');
            Route::put('stages/{stage}', [StageController::class, 'update'])->name('stages.update');
            Route::delete('stages/{stage}', [StageController::class, 'destroy'])->name('stages.destroy');
        });

        Route::middleware('can:fields.manage')->group(function () {
            Route::get('fields', [LeadFieldController::class, 'index'])->name('fields.index');
            Route::post('fields', [LeadFieldController::class, 'store'])->name('fields.store');
            Route::put('fields/reorder', [LeadFieldController::class, 'reorder'])->name('fields.reorder');
            Route::put('fields/{field}', [LeadFieldController::class, 'update'])->name('fields.update');
            Route::delete('fields/{field}', [LeadFieldController::class, 'destroy'])->name('fields.destroy');
        });
    });

    // Roles y permisos
    Route::get('roles', [RoleController::class, 'index'])->middleware('can:roles.view')->name('roles.index');
    Route::get('roles/create', [RoleController::class, 'create'])->middleware('can:roles.create')->name('roles.create');
    Route::post('roles', [RoleController::class, 'store'])->middleware('can:roles.create')->name('roles.store');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->middleware('can:roles.view')->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('can:roles.update')->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete')->name('roles.destroy');
});

require __DIR__.'/settings.php';
