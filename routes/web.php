<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierOrderController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use App\Models\User;

require __DIR__ . '/auth.php';


Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : view('welcome');
});

/**
 * Переключение языка.
 * Поддерживаем любые папки внутри `lang/<locale>`.
 */
Route::get('/language/{locale}', function (string $locale, Request $request) {
    if (! is_dir(lang_path($locale))) {
        abort(404);
    }

    $request->session()->put('locale', $locale);

    return redirect()->back();
})->name('language.switch');


/**
 * Страница дашборда (заглушка данных), чтобы авторизованный пользователь видел интерфейс.
 * Остальные разделы защищаем дальше по мере добавления роутов/контроллеров.
 */


Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', function () {
        return view('dashboard', [
            'stats' => [
                'clients' => 0,
                'orders_in_work' => 0,
                'tasks_today' => 0,
                'accumulated_bonuses' => 0,
            ],
        ]);
    })->name('dashboard');

    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');

    // JSON endpoints for AJAX CRUD
    Route::get('/clients/search', [ClientController::class, 'search'])->name('clients.search');
    Route::post('/clients/add', [ClientController::class, 'save'])->name('clients.add_client');
    Route::delete('/clients/delete/{clientId}', [ClientController::class, 'destroy'])
        ->whereNumber('clientId')
        ->name('clients.delete_client');
    Route::patch('/clients/{clientId}/status', [ClientController::class, 'updateStatus'])
        ->whereNumber('clientId')
        ->name('clients.update_status');

    Route::delete('/clients/{clientId}/files/{fileIndex}', [ClientController::class, 'deleteFile'])
        ->whereNumber('clientId')
        ->whereNumber('fileIndex')
        ->name('clients.delete_file');

    Route::get('/clients/{clientId}', [ClientController::class, 'show'])
        ->whereNumber('clientId')
        ->name('clients.show');

    // Passport objects (CRUD + JSON endpoints for AJAX)
    Route::get('/objects', [\App\Http\Controllers\PassportObject::class, 'index'])
        ->name('objects.index');

    Route::get('/objects/search', [\App\Http\Controllers\PassportObject::class, 'search'])
        ->name('objects.search');

    Route::post('/objects/add', [\App\Http\Controllers\PassportObject::class, 'save'])
        ->name('objects.add_object');

    Route::delete('/objects/delete/{objectId}', [\App\Http\Controllers\PassportObject::class, 'destroy'])
        ->whereNumber('objectId')
        ->name('objects.delete_object');

    Route::patch('/objects/{objectId}/status', [\App\Http\Controllers\PassportObject::class, 'updateStatus'])
        ->whereNumber('objectId')
        ->name('objects.update_status');

    Route::delete('/objects/{objectId}/files/{fileIndex}', [\App\Http\Controllers\PassportObject::class, 'deleteFile'])
        ->whereNumber('objectId')
        ->whereNumber('fileIndex')
        ->name('objects.delete_file');

    Route::get('/objects/{objectId}', [\App\Http\Controllers\PassportObject::class, 'show'])
        ->whereNumber('objectId')
        ->name('objects.show');

    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{supplierId}', [SupplierController::class, 'show'])
        ->whereNumber('supplierId')
        ->name('suppliers.show');
    Route::put('/suppliers/{supplierId}', [SupplierController::class, 'update'])
        ->whereNumber('supplierId')
        ->name('suppliers.update');
    Route::delete('/suppliers/{supplierId}', [SupplierController::class, 'destroy'])
        ->whereNumber('supplierId')
        ->name('suppliers.destroy');
    Route::post('/suppliers/{supplierId}/toggle-favorite', [SupplierController::class, 'toggleFavorite'])
        ->whereNumber('supplierId')
        ->name('suppliers.toggle_favorite');

    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{projectId}', [ProjectController::class, 'show'])
        ->whereNumber('projectId')
        ->name('projects.show');
    Route::put('/projects/{projectId}', [ProjectController::class, 'update'])
        ->whereNumber('projectId')
        ->name('projects.update');
    Route::delete('/projects/{projectId}', [ProjectController::class, 'destroy'])
        ->whereNumber('projectId')
        ->name('projects.destroy');
    Route::patch('/projects/{projectId}/status', [ProjectController::class, 'updateStatus'])
        ->whereNumber('projectId')
        ->name('projects.update_status');
    Route::post('/projects/templates', [ProjectController::class, 'saveTemplate'])->name('projects.templates.store');
    Route::delete('/projects/templates/{templateId}', [ProjectController::class, 'deleteTemplate'])
        ->whereNumber('templateId')
        ->name('projects.templates.destroy');

    // Supplier orders
    Route::get('/supplier-orders', [SupplierOrderController::class, 'index'])->name('supplier-orders.index');
    Route::post('/supplier-orders', [SupplierOrderController::class, 'store'])->name('supplier-orders.store');
    Route::get('/supplier-orders/{orderId}', [SupplierOrderController::class, 'show'])
        ->whereNumber('orderId')
        ->name('supplier-orders.show');
    Route::put('/supplier-orders/{orderId}', [SupplierOrderController::class, 'update'])
        ->whereNumber('orderId')
        ->name('supplier-orders.update');
    Route::delete('/supplier-orders/{orderId}', [SupplierOrderController::class, 'destroy'])
        ->whereNumber('orderId')
        ->name('supplier-orders.destroy');
    Route::patch('/supplier-orders/{orderId}/status', [SupplierOrderController::class, 'updateStatus'])
        ->whereNumber('orderId')
        ->name('supplier-orders.update_status');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
});
