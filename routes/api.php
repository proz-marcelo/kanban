<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ColumnController;
use App\Http\Controllers\CardController;

    // Público
    Route::get('/boards', [BoardController::class, 'index']);
    Route::get('/boards/{id}', [BoardController::class, 'show']);
    Route::get('/boards/{id}/history', [BoardController::class, 'history']);

    // Auth
    Route::post('/login',   [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Protegido
    Route::middleware(['auth.token'])->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);

        // Boards (dono)
        Route::middleware(['ensure.owner:board'])->group(function () {
            Route::patch('/boards/{id}', [BoardController::class, 'update']);
            Route::delete('/boards/{id}', [BoardController::class, 'destroy']);
        });
        Route::post('/boards', [BoardController::class, 'store']); // criar define o owner = auth

        // Columns (dono)
        Route::post('/boards/{id}/columns', [ColumnController::class, 'store'])->middleware('ensure.owner:board');
        Route::patch('/columns/{id}', [ColumnController::class, 'update'])->middleware('ensure.owner:column');
        Route::delete('/columns/{id}', [ColumnController::class, 'destroy'])->middleware('ensure.owner:column');

        // Cards (qualquer logado)
        Route::post('/boards/{id}/cards', [CardController::class, 'store']);
        Route::patch('/cards/{id}', [CardController::class, 'update']);
        Route::patch('/cards/{id}/move', [CardController::class, 'move']);
        Route::delete('/cards/{id}', [CardController::class, 'destroy']);
    });
