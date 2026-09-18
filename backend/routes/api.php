<?php

use App\Modules\Endpoint\Http\Controllers\ScanEventController;
use App\Modules\Endpoint\Http\Controllers\SignatureController;
use App\Modules\Endpoint\Http\Controllers\TerminalController;
use App\Services\CommandCenter\Http\Controllers\AlertController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // POST /terminals/register : authentifié par organisation_token (partagé
    // par l'organisation, pas par terminal) — throttle pour limiter l'abus
    // d'énumération (corrige C1, revue backend), sans bloquer un terminal
    // légitime qui retente après une coupure réseau.
    Route::post('/terminals/register', [TerminalController::class, 'register'])
        ->middleware('throttle:20,1');

    // scan-events/signatures/delta : authentifiés par le token d'accès
    // terminal délivré à l'enrôlement (corrige M3, revue backend) — le
    // terminal_id seul n'est pas un secret.
    Route::middleware(['terminal.auth', 'throttle:60,1'])->group(function () {
        Route::get('/signatures/delta', [SignatureController::class, 'delta']);
        Route::post('/scan-events', [ScanEventController::class, 'store']);
    });

    // Command Center : réservé aux utilisateurs authentifiés de l'organisation.
    Route::middleware(['auth:sanctum', 'scope.organisation'])->group(function () {
        Route::get('/alerts', [AlertController::class, 'index']);
        Route::post('/alerts/{id}/acknowledge', [AlertController::class, 'acknowledge']);
    });
});
