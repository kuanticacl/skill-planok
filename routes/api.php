<?php

use App\Http\Controllers\Api\LeadIngestController;
use App\Http\Middleware\AuthenticateLeadSource;
use Illuminate\Support\Facades\Route;

/*
| API de ingreso de leads. La API key (por origen) se envía en el header X-Api-Key.
*/
Route::prefix('v1')->middleware([AuthenticateLeadSource::class, 'throttle:lead-ingest'])->group(function () {
    Route::post('leads', [LeadIngestController::class, 'store'])->name('api.leads.store');
});
