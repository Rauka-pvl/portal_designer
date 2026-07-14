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

    // Списки кабинета дизайнера (только чтение)
    Route::get('/clients', [DesignerDataController::class, 'clients']);                 // Мои клиенты
    Route::get('/objects', [DesignerDataController::class, 'objects']);                 // Паспорт объекта
    Route::get('/projects', [DesignerDataController::class, 'projects']);               // Проекты
    Route::get('/supplier-orders', [DesignerDataController::class, 'supplierOrders']);  // Поставки
    Route::get('/suppliers', [DesignerDataController::class, 'suppliers']);             // Поставщики
});
