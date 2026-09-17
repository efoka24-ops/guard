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
    // Endpoints terminal <-> backend : authentifiés par organisation_token
    // (device_hash) plutôt que Sanctum — un terminal n'est pas un Utilisateur.
    Route::post('/terminals/register', [TerminalController::class, 'register']);
    Route::get('/signatures/delta', [SignatureController::class, 'delta']);
    Route::post('/scan-events', [ScanEventController::class, 'store']);

    // Command Center : réservé aux utilisateurs authentifiés de l'organisation.
    Route::middleware(['auth:sanctum', 'scope.organisation'])->group(function () {
        Route::get('/alerts', [AlertController::class, 'index']);
        Route::post('/alerts/{id}/acknowledge', [AlertController::class, 'acknowledge']);
    });
});
