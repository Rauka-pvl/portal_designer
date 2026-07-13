<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API — авторизация (для React / мобильного приложения)
|--------------------------------------------------------------------------
|
| Базовый URL: /api/...
| Токен: Authorization: Bearer {token}
|
*/

// Публичные
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Только с токеном
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
