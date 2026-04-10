<?php

use App\Http\Controllers\Designer\ChecklistStepController;
use App\Http\Controllers\Designer\ClientController;
use App\Http\Controllers\Designer\DashboardCalendarController;
use App\Http\Controllers\Designer\NotificationController;
use App\Http\Controllers\Designer\PassportObject;
use App\Http\Controllers\Designer\ProjectController;
use App\Http\Controllers\Designer\ReferralSupplierController;
use App\Http\Controllers\Designer\SettingsController;
use App\Http\Controllers\Designer\SupplierController;
use App\Http\Controllers\Designer\SupplierOrderController;
use App\Http\Controllers\Moderator\ModeratorController;
use App\Http\Controllers\Supplier\SupplierPortalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/', function () {
    if (! Auth::check()) {
        return view('auth.login');
    }

    return match (Auth::user()->role) {
        'moderator' => redirect()->route('moderator.index'),
        'supplier' => redirect()->route('supplier.index'),
        default => redirect()->route('dashboard'),
    };
});

Route::middleware(['auth', 'role:supplier'])->group(function () {
    Route::get('/supplier', [SupplierPortalController::class, 'orders'])->name('supplier.index');
    Route::get('/supplier/company', [SupplierPortalController::class, 'company'])->name('supplier.company');
    Route::post('/supplier/profile', [SupplierPortalController::class, 'saveProfile'])->name('supplier.profile.save');
    Route::patch('/supplier/orders/{orderId}/status', [SupplierPortalController::class, 'updateOrderStatus'])
        ->whereNumber('orderId')
        ->name('supplier.orders.update_status');
});

Route::get('/language/{locale}', function (string $locale, Request $request) {
    if (! is_dir(lang_path($locale))) {
        abort(404);
    }

    $request->session()->put('locale', $locale);

    return redirect()->back();
})->name('language.switch');

Route::get('/referrals/suppliers/create', [ReferralSupplierController::class, 'create'])
    ->name('referrals.suppliers.create');
Route::post('/referrals/suppliers', [ReferralSupplierController::class, 'store'])
    ->name('referrals.suppliers.store');

Route::middleware(['auth', 'role:designer'])->group(function () {
    Route::get('/dashboard', function () {
        return view('designer.dashboard', [
            'stats' => [
                'clients' => 0,
                'orders_in_work' => 0,
                'tasks_today' => 0,
                'accumulated_bonuses' => 0,
            ],
        ]);
    })->name('dashboard');

    Route::get('/dashboard/events', [DashboardCalendarController::class, 'events'])
        ->name('dashboard.events');

    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');

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

    Route::get('/objects', [PassportObject::class, 'index'])
        ->name('objects.index');

    Route::get('/objects/search', [PassportObject::class, 'search'])
        ->name('objects.search');

    Route::post('/objects/add', [PassportObject::class, 'save'])
        ->name('objects.add_object');

    Route::delete('/objects/delete/{objectId}', [PassportObject::class, 'destroy'])
        ->whereNumber('objectId')
        ->name('objects.delete_object');

    Route::patch('/objects/{objectId}/status', [PassportObject::class, 'updateStatus'])
        ->whereNumber('objectId')
        ->name('objects.update_status');

    Route::delete('/objects/{objectId}/files/{fileIndex}', [PassportObject::class, 'deleteFile'])
        ->whereNumber('objectId')
        ->whereNumber('fileIndex')
        ->name('objects.delete_file');

    Route::get('/objects/{objectId}', [PassportObject::class, 'show'])
        ->whereNumber('objectId')
        ->name('objects.show');

    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/supplier-network', [SupplierController::class, 'network'])->name('suppliers.network');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/search-by-inn', [SupplierController::class, 'searchByInn'])->name('suppliers.search_by_inn');
    Route::post('/suppliers/{supplierId}/invite', [SupplierController::class, 'invite'])
        ->whereNumber('supplierId')
        ->name('suppliers.invite');
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

    Route::get('/checklist-steps/{stepId}', [ChecklistStepController::class, 'show'])
        ->whereNumber('stepId')
        ->name('checklist-steps.show');
    Route::put('/checklist-steps/{stepId}', [ChecklistStepController::class, 'update'])
        ->whereNumber('stepId')
        ->name('checklist-steps.update');

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

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markRead'])
        ->whereNumber('notificationId')
        ->name('notifications.read');
    Route::delete('/notifications/{notificationId}', [NotificationController::class, 'destroy'])
        ->whereNumber('notificationId')
        ->name('notifications.destroy');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read_all');
    Route::post('/notifications/{notificationId}/confirm-referral-supplier', [NotificationController::class, 'confirmReferralSupplier'])
        ->whereNumber('notificationId')
        ->name('notifications.confirm_referral_supplier');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('notifications.unread_count');
});

Route::middleware(['auth', 'role:moderator'])->group(function () {
    Route::get('/moderator', [ModeratorController::class, 'index'])->name('moderator.index');
    Route::get('/moderator/history', [ModeratorController::class, 'history'])->name('moderator.history');
    Route::post('/moderator/history/suppliers/{supplierId}', [ModeratorController::class, 'historySupplierUpdate'])
        ->whereNumber('supplierId')
        ->name('moderator.history.suppliers.update');
    Route::post('/moderator/history/objects/{objectId}', [ModeratorController::class, 'historyObjectUpdate'])
        ->whereNumber('objectId')
        ->name('moderator.history.objects.update');

    Route::get('/moderator/suppliers/{supplierId}', [ModeratorController::class, 'supplierShow'])
        ->whereNumber('supplierId')
        ->name('moderator.suppliers.show');
    Route::post('/moderator/suppliers/{supplierId}/decision', [ModeratorController::class, 'supplierDecide'])
        ->whereNumber('supplierId')
        ->name('moderator.suppliers.decision');

    Route::get('/moderator/objects/{objectId}', [ModeratorController::class, 'objectShow'])
        ->whereNumber('objectId')
        ->name('moderator.objects.show');
    Route::post('/moderator/objects/{objectId}/decision', [ModeratorController::class, 'objectDecide'])
        ->whereNumber('objectId')
        ->name('moderator.objects.decision');
});

Route::middleware(['auth', 'role:designer|moderator'])->group(function () {
    Route::get('/profile', [SettingsController::class, 'profile'])->name('profile.show');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
});
