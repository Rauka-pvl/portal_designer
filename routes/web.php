<?php

use App\Http\Controllers\ProductQrController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\Designer\CashbackController;
use App\Http\Controllers\Designer\ChecklistStepController;
use App\Http\Controllers\Designer\ClientController;
use App\Http\Controllers\Designer\DashboardCalendarController;
use App\Http\Controllers\Designer\DashboardController;
use App\Http\Controllers\Designer\NotificationController;
use App\Http\Controllers\Designer\PassportObject;
use App\Http\Controllers\Designer\PipelineController;
use App\Http\Controllers\Designer\ProjectActivityController;
use App\Http\Controllers\Designer\ProjectController;
use App\Http\Controllers\Designer\ReferralSupplierController;
use App\Http\Controllers\Designer\SettingsController;
use App\Http\Controllers\Designer\SubscriptionController;
use App\Http\Controllers\Designer\SupplierCatalogController;
use App\Http\Controllers\Designer\SupplierController;
use App\Http\Controllers\Designer\SupplierOrderController;
use App\Http\Controllers\Designer\TasksController;
use App\Http\Controllers\SupplierOrderChatController;
use App\Http\Controllers\Moderator\ModeratorController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Supplier\CalendarController as SupplierCalendarController;
use App\Http\Controllers\Supplier\DepositController as SupplierDepositController;
use App\Http\Controllers\Supplier\DesignerDirectoryController;
use App\Http\Controllers\Supplier\ProductController as SupplierProductController;
use App\Http\Controllers\Supplier\SupplierPortalController;
use App\Http\Controllers\Supplier\SettingsController as SupplierSettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/', function () {
    if (! Auth::check()) {
        return view('auth.login');
    }

    $user = Auth::user();

    return match ($user->role) {
        'moderator' => redirect()->route('moderator.index'),
        'supplier' => redirect()->route(\App\Support\SupplierDeposit::redirectRoute($user)),
        default => redirect()->route(\App\Support\DesignerSubscription::redirectRoute($user)),
    };
});

Route::get('/faq', function (Request $request) {
    $user = $request->user();
    $role = $user?->role;

    $layout = match ($role) {
        'supplier' => 'layouts.supplier',
        'designer', 'moderator' => 'layouts.dashboard',
        default => 'layouts.public',
    };

    return view('faq.index', [
        'layout' => $layout,
        'topics' => config('faq.topics', []),
    ]);
})->name('faq.index');

// Stable product QR entry (no auth — redirects to login/card).
Route::get('/q/{token}', [ProductQrController::class, 'resolve'])
    ->where('token', '[A-Za-z0-9]{16,64}')
    ->name('product.qr.resolve');

Route::middleware(['auth', 'role:supplier', 'password.changed'])->group(function () {
    Route::get('/supplier/deposit', [SupplierDepositController::class, 'index'])->name('supplier.deposit.index');
    Route::post('/supplier/deposit/create', [SupplierDepositController::class, 'create'])->name('supplier.deposit.create');
    Route::get('/supplier/deposit/checkout/{payment}', [SupplierDepositController::class, 'checkout'])
        ->name('supplier.deposit.checkout');
    Route::get('/supplier/deposit/return/{payment}', [SupplierDepositController::class, 'returnFromProvider'])
        ->name('supplier.deposit.return');
    Route::post('/supplier/deposit/confirm/{payment}', [SupplierDepositController::class, 'confirmDemo'])
        ->name('supplier.deposit.confirm');
    Route::post('/supplier/deposit/cancel/{payment}', [SupplierDepositController::class, 'cancel'])
        ->name('supplier.deposit.cancel');
    Route::get('/supplier/deposit/status/{payment}', [SupplierDepositController::class, 'status'])
        ->name('supplier.deposit.status');
    Route::post('/supplier/deposit/retry', [SupplierDepositController::class, 'retry'])->name('supplier.deposit.retry');
});

Route::middleware(['auth', 'role:supplier', 'password.changed', 'deposit.paid'])->group(function () {
    Route::get('/supplier', [SupplierCalendarController::class, 'index'])->name('supplier.index');
    Route::get('/supplier/orders', [SupplierPortalController::class, 'orders'])->name('supplier.orders');
    Route::get('/supplier/calendar/events', [SupplierCalendarController::class, 'events'])->name('supplier.calendar.events');
    Route::get('/supplier/company', [SupplierPortalController::class, 'company'])->name('supplier.company');
    Route::post('/supplier/profile', [SupplierPortalController::class, 'saveProfile'])->name('supplier.profile.save');
    Route::get('/supplier/profile/view', [SupplierSettingsController::class, 'profile'])->name('supplier.profile.show');
    Route::get('/supplier/settings', [SupplierSettingsController::class, 'index'])->name('supplier.settings.index');
    Route::put('/supplier/settings/profile', [SupplierSettingsController::class, 'updateProfile'])->name('supplier.settings.profile.update');
    Route::put('/supplier/settings/password', [SupplierSettingsController::class, 'updatePassword'])->name('supplier.settings.password.update');
    Route::patch('/supplier/orders/{orderId}/status', [SupplierPortalController::class, 'updateOrderStatus'])
        ->whereNumber('orderId')
        ->name('supplier.orders.update_status');
    Route::post('/supplier/orders/{orderId}/offer/accept', [SupplierPortalController::class, 'acceptOffer'])
        ->whereNumber('orderId')
        ->name('supplier.orders.offer.accept');
    Route::post('/supplier/orders/{orderId}/offer/reject', [SupplierPortalController::class, 'rejectOffer'])
        ->whereNumber('orderId')
        ->name('supplier.orders.offer.reject');
    Route::post('/supplier/orders/{orderId}/offer/counter', [SupplierPortalController::class, 'counterOffer'])
        ->whereNumber('orderId')
        ->name('supplier.orders.offer.counter');

    Route::get('/supplier/products', [SupplierProductController::class, 'index'])->name('supplier.products.index');
    Route::get('/supplier/products/template', [SupplierProductController::class, 'template'])->name('supplier.products.template');
    Route::post('/supplier/products/import', [SupplierProductController::class, 'import'])->name('supplier.products.import');
    Route::post('/supplier/products', [SupplierProductController::class, 'store'])->name('supplier.products.store');
    Route::get('/supplier/products/{productId}', [SupplierProductController::class, 'show'])
        ->whereNumber('productId')
        ->name('supplier.products.show');
    Route::post('/supplier/products/{productId}', [SupplierProductController::class, 'update'])
        ->whereNumber('productId')
        ->name('supplier.products.update');
    Route::post('/supplier/products/{productId}/image', [SupplierProductController::class, 'updateImage'])
        ->whereNumber('productId')
        ->name('supplier.products.image');
    Route::delete('/supplier/products/{productId}', [SupplierProductController::class, 'destroy'])
        ->whereNumber('productId')
        ->name('supplier.products.destroy');
    Route::get('/supplier/products/{productId}/qr', [ProductQrController::class, 'modalData'])
        ->whereNumber('productId')
        ->name('supplier.products.qr');
    Route::get('/supplier/products/{productId}/qr/download/{format}', [ProductQrController::class, 'download'])
        ->whereNumber('productId')
        ->whereIn('format', ['png', 'svg'])
        ->name('supplier.products.qr.download');
    Route::get('/supplier/products/{productId}/qr/print', [ProductQrController::class, 'printCard'])
        ->whereNumber('productId')
        ->name('supplier.products.qr.print');
    Route::post('/supplier/products/{productId}/qr/reissue', [ProductQrController::class, 'reissue'])
        ->whereNumber('productId')
        ->name('supplier.products.qr.reissue');

    Route::get('/supplier/designers', [DesignerDirectoryController::class, 'index'])->name('supplier.designers.index');
    Route::get('/supplier/designers/{designerId}', [DesignerDirectoryController::class, 'show'])
        ->whereNumber('designerId')
        ->name('supplier.designers.show');
    Route::get('/supplier/designers/{designerId}/reviews', [DesignerDirectoryController::class, 'reviews'])
        ->whereNumber('designerId')
        ->name('supplier.designers.reviews');

    Route::get('/supplier/profile/reviews', [ReviewController::class, 'index'])->name('supplier.profile.reviews');
    Route::post('/supplier/reviews', [ReviewController::class, 'store'])->name('supplier.reviews.store');

    Route::get('/supplier/notifications', [NotificationController::class, 'index'])->name('supplier.notifications.index');
    Route::post('/supplier/notifications/{notificationId}/read', [NotificationController::class, 'markRead'])
        ->whereNumber('notificationId')
        ->name('supplier.notifications.read');
    Route::post('/supplier/notifications/{notificationId}/unread', [NotificationController::class, 'markUnread'])
        ->whereNumber('notificationId')
        ->name('supplier.notifications.unread');
    Route::delete('/supplier/notifications/{notificationId}', [NotificationController::class, 'destroy'])
        ->whereNumber('notificationId')
        ->name('supplier.notifications.destroy');
    Route::post('/supplier/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('supplier.notifications.read_all');
    Route::get('/supplier/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('supplier.notifications.unread_count');
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

Route::middleware(['auth', 'role:designer', 'subscription.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/dashboard/events', [DashboardCalendarController::class, 'events'])
        ->name('dashboard.events');

    Route::get('/tasks', [TasksController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/events', [DashboardCalendarController::class, 'events'])->name('tasks.events');
    Route::get('/tasks/data', [TasksController::class, 'data'])->name('tasks.data');
    Route::post('/tasks', [TasksController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [TasksController::class, 'show'])->whereNumber('task')->name('tasks.show');
    Route::put('/tasks/{task}', [TasksController::class, 'update'])->whereNumber('task')->name('tasks.update');
    Route::patch('/tasks/{task}/status', [TasksController::class, 'updateStatus'])->whereNumber('task')->name('tasks.status');
    Route::delete('/tasks/{task}', [TasksController::class, 'destroy'])->whereNumber('task')->name('tasks.destroy');

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
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{supplierId}', [SupplierController::class, 'show'])
        ->whereNumber('supplierId')
        ->name('suppliers.show');
    Route::get('/suppliers/{supplierId}/reviews', [SupplierController::class, 'reviews'])
        ->whereNumber('supplierId')
        ->name('suppliers.reviews');
    Route::put('/suppliers/{supplierId}', [SupplierController::class, 'update'])
        ->whereNumber('supplierId')
        ->name('suppliers.update');
    Route::delete('/suppliers/{supplierId}', [SupplierController::class, 'destroy'])
        ->whereNumber('supplierId')
        ->name('suppliers.destroy');
    Route::post('/suppliers/{supplierId}/toggle-favorite', [SupplierController::class, 'toggleFavorite'])
        ->whereNumber('supplierId')
        ->name('suppliers.toggle_favorite');

    Route::get('/suppliers/{supplierId}/products', [SupplierCatalogController::class, 'index'])
        ->whereNumber('supplierId')
        ->name('suppliers.products.index');
    Route::get('/suppliers/{supplierId}/products.json', [SupplierCatalogController::class, 'productsJson'])
        ->whereNumber('supplierId')
        ->name('suppliers.products.json');
    Route::get('/suppliers/{supplierId}/products/{productId}', [SupplierCatalogController::class, 'show'])
        ->whereNumber('supplierId')
        ->whereNumber('productId')
        ->name('suppliers.products.show');

    Route::get('/suppliers/{supplierId}/products/{productId}/qr/select-project', [ProductQrController::class, 'selectProject'])
        ->whereNumber('supplierId')
        ->whereNumber('productId')
        ->name('product.qr.select-project');
    Route::post('/suppliers/{supplierId}/products/{productId}/qr/assign-project', [ProductQrController::class, 'assignToProject'])
        ->whereNumber('supplierId')
        ->whereNumber('productId')
        ->name('product.qr.assign-project');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/templates', [ProjectController::class, 'templates'])->name('projects.templates.index');
    Route::post('/projects/templates', [ProjectController::class, 'saveTemplate'])->name('projects.templates.store');
    Route::delete('/projects/templates/{templateId}', [ProjectController::class, 'deleteTemplate'])
        ->whereNumber('templateId')
        ->name('projects.templates.destroy');
    Route::get('/projects/{projectId}', [ProjectController::class, 'show'])
        ->whereNumber('projectId')
        ->name('projects.show');
    Route::put('/projects/{projectId}', [ProjectController::class, 'update'])
        ->whereNumber('projectId')
        ->name('projects.update');
    Route::delete('/projects/{projectId}', [ProjectController::class, 'destroy'])
        ->whereNumber('projectId')
        ->name('projects.destroy');
    Route::delete('/projects/{projectId}/files/{fileIndex}', [ProjectController::class, 'deleteFile'])
        ->whereNumber('projectId')
        ->whereNumber('fileIndex')
        ->name('projects.delete_file');
    Route::patch('/projects/{projectId}/status', [ProjectController::class, 'updateStatus'])
        ->whereNumber('projectId')
        ->name('projects.update_status');
    Route::get('/projects/{projectId}/activity', [ProjectActivityController::class, 'index'])
        ->whereNumber('projectId')
        ->name('projects.activity');
    Route::post('/projects/{projectId}/comments', [ProjectActivityController::class, 'storeComment'])
        ->whereNumber('projectId')
        ->name('projects.comments.store');

    Route::get('/pipelines/project', [PipelineController::class, 'showProjectPipeline'])->name('pipelines.project');
    Route::get('/pipelines/supply', [PipelineController::class, 'showSupplyPipeline'])->name('pipelines.supply');
    Route::get('/pipelines/client', [PipelineController::class, 'showClientPipeline'])->name('pipelines.client');
    Route::post('/pipelines/stages', [PipelineController::class, 'storeStage'])->name('pipelines.stages.store');
    Route::put('/pipelines/stages/{stageId}', [PipelineController::class, 'updateStage'])
        ->whereNumber('stageId')
        ->name('pipelines.stages.update');
    Route::post('/pipelines/reorder', [PipelineController::class, 'reorder'])->name('pipelines.reorder');
    Route::post('/pipelines/sync', [PipelineController::class, 'sync'])->name('pipelines.sync');
    Route::delete('/pipelines/stages/{stageId}', [PipelineController::class, 'destroyStage'])
        ->whereNumber('stageId')
        ->name('pipelines.stages.destroy');

    Route::get('/checklist-steps/{stepId}', [ChecklistStepController::class, 'show'])
        ->whereNumber('stepId')
        ->name('checklist-steps.show');
    Route::put('/checklist-steps/{stepId}', [ChecklistStepController::class, 'update'])
        ->whereNumber('stepId')
        ->name('checklist-steps.update');

    Route::get('/supplier-orders', function (\Illuminate\Http\Request $request) {
        // Global supplies menu removed — keep route for deep links / legacy redirects into Projects CRM.
        $projectId = $request->query('project_id');
        if ($projectId) {
            return redirect()->route('projects.index', [
                'open' => $projectId,
                'tab' => 'supplies',
                'create' => $request->query('create'),
            ]);
        }

        return redirect()->route('projects.index')->with('status', __('supplier-orders.moved_into_projects'));
    })->name('supplier-orders.index');
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
    Route::delete('/supplier-orders/{orderId}/files/{fileIndex}', [SupplierOrderController::class, 'deleteFile'])
        ->whereNumber('orderId')
        ->whereNumber('fileIndex')
        ->name('supplier-orders.delete_file');
    Route::patch('/supplier-orders/{orderId}/status', [SupplierOrderController::class, 'updateStatus'])
        ->whereNumber('orderId')
        ->name('supplier-orders.update_status');
    Route::post('/supplier-orders/{orderId}/offer/accept', [SupplierOrderController::class, 'acceptOffer'])
        ->whereNumber('orderId')
        ->name('supplier-orders.offer.accept');
    Route::post('/supplier-orders/{orderId}/offer/reject', [SupplierOrderController::class, 'rejectOffer'])
        ->whereNumber('orderId')
        ->name('supplier-orders.offer.reject');
    Route::post('/supplier-orders/{orderId}/offer/counter', [SupplierOrderController::class, 'counterOffer'])
        ->whereNumber('orderId')
        ->name('supplier-orders.offer.counter');

    Route::get('/profile/reviews', [ReviewController::class, 'index'])->name('profile.reviews');
    Route::get('/profile/cashback', [CashbackController::class, 'index'])->name('profile.cashback');
    Route::get('/profile/cashback/withdraw', [CashbackController::class, 'withdrawForm'])->name('profile.cashback.withdraw');
    Route::post('/profile/cashback/withdraw', [CashbackController::class, 'withdraw'])->name('profile.cashback.withdraw.store');
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markRead'])
        ->whereNumber('notificationId')
        ->name('notifications.read');
    Route::post('/notifications/{notificationId}/unread', [NotificationController::class, 'markUnread'])
        ->whereNumber('notificationId')
        ->name('notifications.unread');
    Route::delete('/notifications/{notificationId}', [NotificationController::class, 'destroy'])
        ->whereNumber('notificationId')
        ->name('notifications.destroy');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read_all');
    Route::post('/notifications/{notificationId}/confirm-referral-supplier', [NotificationController::class, 'confirmReferralSupplier'])
        ->whereNumber('notificationId')
        ->name('notifications.confirm_referral_supplier');
    Route::post('/notifications/{notificationId}/team-invite/accept', [NotificationController::class, 'acceptTeamInvite'])
        ->whereNumber('notificationId')
        ->name('notifications.team_invite_accept');
    Route::post('/notifications/{notificationId}/team-invite/decline', [NotificationController::class, 'declineTeamInvite'])
        ->whereNumber('notificationId')
        ->name('notifications.team_invite_decline');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('notifications.unread_count');
});

Route::middleware(['auth', 'role:moderator'])->group(function () {
    Route::get('/moderator/support', [\App\Http\Controllers\Admin\SupportAdminController::class, 'index'])
        ->name('admin.support.index');
    Route::get('/moderator/support/{ticket}', [\App\Http\Controllers\Admin\SupportAdminController::class, 'show'])
        ->whereNumber('ticket')
        ->name('admin.support.show');
    Route::post('/moderator/support/{ticket}/reply', [\App\Http\Controllers\Admin\SupportAdminController::class, 'reply'])
        ->whereNumber('ticket')
        ->middleware('throttle:20,1')
        ->name('admin.support.reply');
    Route::patch('/moderator/support/{ticket}/status', [\App\Http\Controllers\Admin\SupportAdminController::class, 'updateStatus'])
        ->whereNumber('ticket')
        ->name('admin.support.status');
    Route::get('/moderator/support/attachments/{attachment}/download', [\App\Http\Controllers\Designer\SupportController::class, 'download'])
        ->whereNumber('attachment')
        ->name('admin.support.attachments.download');

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

Route::middleware(['auth', 'role:designer|moderator', 'subscription.active'])->group(function () {
    Route::get('/profile', [SettingsController::class, 'profile'])->name('profile.show');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');

    Route::post('/settings/team/members', [\App\Http\Controllers\Designer\TeamController::class, 'addMember'])
        ->name('settings.team.add-member');
    Route::post('/settings/team/invite', [\App\Http\Controllers\Designer\TeamController::class, 'invite'])
        ->name('settings.team.invite');
    Route::post('/settings/team/create', [\App\Http\Controllers\Designer\TeamController::class, 'createMember'])
        ->name('settings.team.create');
    Route::patch('/settings/team/members/{member}', [\App\Http\Controllers\Designer\TeamController::class, 'changeRole'])
        ->name('settings.team.change-role');
    Route::delete('/settings/team/members/{member}', [\App\Http\Controllers\Designer\TeamController::class, 'removeMember'])
        ->name('settings.team.remove');
    Route::delete('/settings/team/invitations/{invitation}', [\App\Http\Controllers\Designer\TeamController::class, 'cancelInvitation'])
        ->name('settings.team.cancel-invitation');
});

Route::middleware(['auth', 'role:designer', 'subscription.active'])->group(function () {
    Route::get('/support', [\App\Http\Controllers\Designer\SupportController::class, 'index'])->name('support.index');
    Route::get('/support/create', [\App\Http\Controllers\Designer\SupportController::class, 'create'])->name('support.create');
    Route::post('/support', [\App\Http\Controllers\Designer\SupportController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('support.store');
    Route::get('/support/attachments/{attachment}/download', [\App\Http\Controllers\Designer\SupportController::class, 'download'])
        ->whereNumber('attachment')
        ->name('support.attachments.download');
    Route::get('/support/{ticket}', [\App\Http\Controllers\Designer\SupportController::class, 'show'])
        ->whereNumber('ticket')
        ->name('support.show');
    Route::post('/support/{ticket}/reply', [\App\Http\Controllers\Designer\SupportController::class, 'reply'])
        ->whereNumber('ticket')
        ->middleware('throttle:20,1')
        ->name('support.reply');
});

Route::middleware(['auth', 'role:designer'])->group(function () {
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('/subscription/checkout/{plan}', [SubscriptionController::class, 'checkout'])
        ->where('plan', '[a-z0-9_-]+')
        ->name('subscription.checkout');
    Route::post('/subscription/purchase', [SubscriptionController::class, 'purchase'])->name('subscription.purchase');
    Route::post('/subscription/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscription.change-plan');
    Route::post('/subscription/payment', [SubscriptionController::class, 'updatePayment'])->name('subscription.payment');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/resume', [SubscriptionController::class, 'resume'])->name('subscription.resume');
});

Route::middleware(['auth', 'role:designer|supplier', 'password.changed', 'subscription.active', 'deposit.paid'])->group(function () {
    Route::get('/supplier-orders/{orderId}/chat/messages', [SupplierOrderChatController::class, 'messages'])
        ->whereNumber('orderId')
        ->name('supplier-orders.chat.messages');
    Route::post('/supplier-orders/{orderId}/chat/messages', [SupplierOrderChatController::class, 'store'])
        ->whereNumber('orderId')
        ->name('supplier-orders.chat.store');
    Route::post('/supplier-orders/{orderId}/chat/read', [SupplierOrderChatController::class, 'markRead'])
        ->whereNumber('orderId')
        ->name('supplier-orders.chat.read');
    Route::get('/supplier-orders/chat/unread-map', [SupplierOrderChatController::class, 'unreadMap'])
        ->name('supplier-orders.chat.unread_map');
    Route::get('/chat/unread-count', [SupplierOrderChatController::class, 'unreadCount'])
        ->name('chat.unread_count');

    Route::get('/community', [CommunityController::class, 'index'])->name('community.index');
    Route::get('/community/my-posts', fn () => redirect()->route('community.index', ['tab' => 'my']))->name('community.my_posts');
    Route::get('/community/saved', fn () => redirect()->route('community.index', ['tab' => 'saved']))->name('community.saved');
    Route::get('/community/post/{postId}', [CommunityController::class, 'show'])->whereNumber('postId')->name('community.post');
    Route::get('/community/profile/{userId}', [CommunityController::class, 'profile'])->whereNumber('userId')->name('community.profile');
    Route::post('/community/posts', [CommunityController::class, 'store'])->name('community.posts.store');
    Route::post('/community/posts/{postId}', [CommunityController::class, 'update'])->whereNumber('postId')->name('community.posts.update');
    Route::delete('/community/posts/{postId}', [CommunityController::class, 'destroy'])->whereNumber('postId')->name('community.posts.destroy');
    Route::post('/community/posts/{postId}/like', [CommunityController::class, 'toggleLike'])->whereNumber('postId')->name('community.posts.like');
    Route::post('/community/posts/{postId}/save', [CommunityController::class, 'toggleSave'])->whereNumber('postId')->name('community.posts.save');
    Route::post('/community/posts/{postId}/comments', [CommunityController::class, 'storeComment'])->whereNumber('postId')->name('community.posts.comments.store');
    Route::put('/community/comments/{commentId}', [CommunityController::class, 'updateComment'])->whereNumber('commentId')->name('community.comments.update');
    Route::delete('/community/comments/{commentId}', [CommunityController::class, 'destroyComment'])->whereNumber('commentId')->name('community.comments.destroy');
    Route::post('/community/posts/{postId}/report', [CommunityController::class, 'report'])->whereNumber('postId')->name('community.posts.report');
    Route::post('/community/posts/{postId}/hide', [CommunityController::class, 'hide'])->whereNumber('postId')->name('community.posts.hide');
});
