<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DesignerCrudController;
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
        // —— Read ——
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

        // —— Write (create / update / delete) ——
        Route::post('/clients', [DesignerCrudController::class, 'storeClient']);
        Route::match(['put', 'patch'], '/clients/{id}', [DesignerCrudController::class, 'updateClient'])->whereNumber('id');
        Route::delete('/clients/{id}', [DesignerCrudController::class, 'destroyClient'])->whereNumber('id');

        Route::post('/objects', [DesignerCrudController::class, 'storeObject']);
        Route::match(['put', 'patch'], '/objects/{id}', [DesignerCrudController::class, 'updateObject'])->whereNumber('id');
        Route::delete('/objects/{id}', [DesignerCrudController::class, 'destroyObject'])->whereNumber('id');

        Route::post('/projects', [DesignerCrudController::class, 'storeProject']);
        Route::match(['put', 'patch'], '/projects/{id}', [DesignerCrudController::class, 'updateProject'])->whereNumber('id');
        Route::delete('/projects/{id}', [DesignerCrudController::class, 'destroyProject'])->whereNumber('id');

        Route::post('/supplier-orders', [DesignerCrudController::class, 'storeSupplierOrder']);
        Route::match(['put', 'patch'], '/supplier-orders/{id}', [DesignerCrudController::class, 'updateSupplierOrder'])->whereNumber('id');
        Route::delete('/supplier-orders/{id}', [DesignerCrudController::class, 'destroySupplierOrder'])->whereNumber('id');

        Route::post('/suppliers', [DesignerCrudController::class, 'storeSupplier']);
        Route::match(['put', 'patch'], '/suppliers/{id}', [DesignerCrudController::class, 'updateSupplier'])->whereNumber('id');
        Route::delete('/suppliers/{id}', [DesignerCrudController::class, 'destroySupplier'])->whereNumber('id');
    });
});
