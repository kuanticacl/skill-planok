<?php

use App\Http\Controllers\Public\EmailViewController;
use App\Http\Controllers\Public\TrackingController;
use App\Http\Controllers\Public\UnsubscribeController;
use App\Http\Controllers\Webhooks\ResendWebhookController;
use Illuminate\Support\Facades\Route;

/*
| Rutas públicas del módulo de email: sin sesión ni CSRF (se abren desde la bandeja de entrada
| del destinatario o las llama Resend). La seguridad va en tokens (uuid) y firmas.
*/
Route::get('t/o/{uuid}.gif', [TrackingController::class, 'open'])->name('track.open');
Route::get('t/c/{uuid}', [TrackingController::class, 'click'])->name('track.click');

Route::get('unsubscribe/{uuid}', [UnsubscribeController::class, 'show'])->name('unsubscribe.show');
Route::post('unsubscribe/{uuid}', [UnsubscribeController::class, 'store'])->name('unsubscribe.store');

Route::get('e/{uuid}', [EmailViewController::class, 'show'])->name('email.view');

Route::post('webhooks/resend', ResendWebhookController::class)->name('webhooks.resend');
