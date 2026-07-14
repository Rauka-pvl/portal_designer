<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DesignerDataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API — для React / мобильного приложения
|--------------------------------------------------------------------------
|
| Базовый URL: /api/...
| Токен: Authorization: Bearer {token}
|
*/

// ——— Авторизация (публичные) ———
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ——— С токеном ———
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Данные дизайнера — только при активной подписке / триале
    Route::middleware('subscription.active')->group(function () {
        Route::get('/clients', [DesignerDataController::class, 'clients']);
        Route::get('/objects', [DesignerDataController::class, 'objects']);
        Route::get('/projects', [DesignerDataController::class, 'projects']);
        Route::get('/supplier-orders', [DesignerDataController::class, 'supplierOrders']);
        Route::get('/suppliers', [DesignerDataController::class, 'suppliers']);

        Route::get('/clients/{id}', [DesignerDataController::class, 'client'])->whereNumber('id');
        Route::get('/objects/{id}', [DesignerDataController::class, 'object'])->whereNumber('id');
        Route::get('/projects/{id}', [DesignerDataController::class, 'project'])->whereNumber('id');
        Route::get('/supplier-orders/{id}', [DesignerDataController::class, 'supplierOrder'])->whereNumber('id');
        Route::get('/suppliers/{id}', [DesignerDataController::class, 'supplier'])->whereNumber('id');
    });
});
