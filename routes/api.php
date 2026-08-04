<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatApiController;
use App\Http\Controllers\Api\ChecklistApiController;
use App\Http\Controllers\Api\ChecklistItemApiController;
use App\Http\Controllers\Api\ChecklistTemplateApiController;
use App\Http\Controllers\Api\ClientApiController;
use App\Http\Controllers\Api\ClientStageApiController;
use App\Http\Controllers\Api\CommunityApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\DesignerCrudController;
use App\Http\Controllers\Api\DesignerDataController;
use App\Http\Controllers\Api\DesignerSupplierApiController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\ProjectStageApiController;
use App\Http\Controllers\Api\SubscriptionApiController;
use App\Http\Controllers\Api\SupplierApiController;
use App\Http\Controllers\Api\SupplyApiController;
use App\Http\Controllers\Api\SupplyItemApiController;
use App\Http\Controllers\Api\TaskApiController;
use App\Http\Controllers\Api\TeamApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API — мобильное приложение дизайнера
|--------------------------------------------------------------------------
|
| Базовый URL: /api/...
| Токен: Authorization: Bearer {token} (Sanctum, без изменений)
|
*/

// OpenAPI UI / JSON (Scramble). Aliases for mobile clients.
Route::redirect('/documentation', '/docs/api');
Route::get('/documentation.json', fn () => redirect('/docs/api.json'));

// ——— Авторизация (публичные) — не менять ———
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/forgot-password', [ProfileController::class, 'forgotPassword'])->middleware('throttle:password-email');
Route::post('/reset-password', [ProfileController::class, 'resetPassword'])->middleware('throttle:password-email');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::match(['put', 'patch'], '/me/profile', [ProfileController::class, 'update']);
    Route::post('/me/password', [ProfileController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Уведомления — без активной подписки
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->whereNumber('id');
    Route::post('/notifications/{id}/unread', [NotificationController::class, 'markUnread'])->whereNumber('id');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->whereNumber('id');
    Route::post('/notifications/{id}/confirm-referral', [NotificationController::class, 'confirmReferralSupplier'])->whereNumber('id');

    // Team invite accept/decline — without personal subscription (seat reserved by pending invite)
    Route::post('/team/invitations/{invitation}/accept', [TeamApiController::class, 'acceptInvitation'])->whereNumber('invitation')->middleware('throttle:20,1');
    Route::post('/team/invitations/{invitation}/decline', [TeamApiController::class, 'declineInvitation'])->whereNumber('invitation')->middleware('throttle:20,1');

    // Подписка (чтение планов/статуса доступно с токеном; checkout требует billing rights)
    Route::get('/subscription/plans', [SubscriptionApiController::class, 'plans']);
    Route::get('/subscription', [SubscriptionApiController::class, 'show']);
    Route::get('/subscription/history', [SubscriptionApiController::class, 'history']);
    Route::post('/subscription/checkout', [SubscriptionApiController::class, 'checkout'])->middleware('throttle:10,1');
    Route::post('/subscription/change-plan', [SubscriptionApiController::class, 'changePlan']);
    Route::post('/subscription/renew', [SubscriptionApiController::class, 'renew']);
    Route::post('/subscription/resume', [SubscriptionApiController::class, 'resume']);
    Route::post('/subscription/cancel', [SubscriptionApiController::class, 'cancel']);

    // Чат и сообщество
    Route::middleware(['subscription.active', 'deposit.paid'])->group(function () {
        // Supplier order chat (designer + supplier)
        Route::get('/supplier-orders/chat/unread-map', [ChatApiController::class, 'unreadMap']);
        Route::get('/chat/unread-count', [ChatApiController::class, 'unreadCount']);
        Route::get('/supplier-orders/{id}/chat/messages', [ChatApiController::class, 'messages'])->whereNumber('id');
        Route::post('/supplier-orders/{id}/chat/messages', [ChatApiController::class, 'store'])->whereNumber('id');
        Route::post('/supplier-orders/{id}/chat/read', [ChatApiController::class, 'markRead'])->whereNumber('id');
        // Supplies aliases (same chat endpoints)
        Route::get('/supplies/{id}/chat/messages', [ChatApiController::class, 'messages'])->whereNumber('id');
        Route::post('/supplies/{id}/chat/messages', [ChatApiController::class, 'store'])->whereNumber('id');
        Route::post('/supplies/{id}/chat/read', [ChatApiController::class, 'markRead'])->whereNumber('id');

        // Community (parity with website)
        Route::get('/community', [CommunityApiController::class, 'index']);
        Route::get('/community/posts/{id}', [CommunityApiController::class, 'show'])->whereNumber('id');
        Route::get('/community/posts/{id}/comments', [CommunityApiController::class, 'comments'])->whereNumber('id');
        Route::get('/community/users/{id}', [CommunityApiController::class, 'profile'])->whereNumber('id');
        Route::post('/community/posts', [CommunityApiController::class, 'store']);
        // POST update alias for multipart (images) — same as website
        Route::post('/community/posts/{id}', [CommunityApiController::class, 'update'])->whereNumber('id');
        Route::match(['put', 'patch'], '/community/posts/{id}', [CommunityApiController::class, 'update'])->whereNumber('id');
        Route::delete('/community/posts/{id}', [CommunityApiController::class, 'destroy'])->whereNumber('id');
        Route::post('/community/posts/{id}/like', [CommunityApiController::class, 'toggleLike'])->whereNumber('id');
        Route::delete('/community/posts/{id}/like', [CommunityApiController::class, 'toggleLike'])->whereNumber('id');
        Route::post('/community/posts/{id}/save', [CommunityApiController::class, 'toggleSave'])->whereNumber('id');
        Route::delete('/community/posts/{id}/save', [CommunityApiController::class, 'toggleSave'])->whereNumber('id');
        Route::post('/community/posts/{id}/comments', [CommunityApiController::class, 'storeComment'])->whereNumber('id');
        Route::match(['put', 'patch'], '/community/comments/{id}', [CommunityApiController::class, 'updateComment'])->whereNumber('id');
        Route::delete('/community/comments/{id}', [CommunityApiController::class, 'destroyComment'])->whereNumber('id');
        Route::post('/community/posts/{id}/report', [CommunityApiController::class, 'report'])->whereNumber('id');
        Route::post('/community/posts/{id}/hide', [CommunityApiController::class, 'hide'])->whereNumber('id');
    });

    // Designer business API
    Route::middleware(['role:designer', 'subscription.active', 'throttle:api-business'])->group(function () {
        Route::get('/dashboard', [DashboardApiController::class, 'index']);

        // Clients
        Route::get('/clients', [ClientApiController::class, 'index']);
        Route::post('/clients', [ClientApiController::class, 'store']);
        Route::get('/clients/{client}', [ClientApiController::class, 'show'])->whereNumber('client');
        Route::match(['put', 'patch'], '/clients/{client}', [ClientApiController::class, 'update'])->whereNumber('client');
        Route::delete('/clients/{client}', [ClientApiController::class, 'destroy'])->whereNumber('client');
        Route::get('/clients/{client}/projects', [ClientApiController::class, 'projects'])->whereNumber('client');
        Route::post('/clients/{client}/files', [ClientApiController::class, 'storeFiles'])->whereNumber('client')->middleware('throttle:30,1');
        Route::delete('/clients/{client}/files/{file}', [ClientApiController::class, 'destroyFile'])->whereNumber(['client', 'file']);

        // Client pipeline stages (same list as website client funnel)
        Route::get('/client-stages', [ClientStageApiController::class, 'index']);
        Route::post('/client-stages', [ClientStageApiController::class, 'store']);
        Route::match(['put', 'patch'], '/client-stages/{stageId}', [ClientStageApiController::class, 'update'])->whereNumber('stageId');
        Route::delete('/client-stages/{stageId}', [ClientStageApiController::class, 'destroy'])->whereNumber('stageId');
        Route::post('/client-stages/reorder', [ClientStageApiController::class, 'reorder']);

        // Projects
        Route::get('/projects', [ProjectApiController::class, 'index']);
        Route::post('/projects', [ProjectApiController::class, 'store']);
        Route::get('/projects/{projectId}', [ProjectApiController::class, 'show'])->whereNumber('projectId');
        Route::match(['put', 'patch'], '/projects/{projectId}', [ProjectApiController::class, 'update'])->whereNumber('projectId');
        Route::delete('/projects/{projectId}', [ProjectApiController::class, 'destroy'])->whereNumber('projectId');
        Route::patch('/projects/{projectId}/stage', [ProjectApiController::class, 'updateStage'])->whereNumber('projectId');
        Route::get('/projects/{projectId}/activity', [ProjectApiController::class, 'activity'])->whereNumber('projectId');
        Route::get('/projects/{projectId}/comments', [ProjectApiController::class, 'comments'])->whereNumber('projectId');
        Route::post('/projects/{projectId}/comments', [ProjectApiController::class, 'storeComment'])->whereNumber('projectId')->middleware('throttle:60,1');

        // Project pipeline stages
        Route::get('/project-stages', [ProjectStageApiController::class, 'index']);
        Route::post('/project-stages', [ProjectStageApiController::class, 'store']);
        Route::match(['put', 'patch'], '/project-stages/{stageId}', [ProjectStageApiController::class, 'update'])->whereNumber('stageId');
        Route::delete('/project-stages/{stageId}', [ProjectStageApiController::class, 'destroy'])->whereNumber('stageId');
        Route::post('/project-stages/reorder', [ProjectStageApiController::class, 'reorder']);

        // Tasks (static paths before {taskId})
        Route::get('/tasks/kanban', [TaskApiController::class, 'kanban']);
        Route::get('/tasks/calendar', [TaskApiController::class, 'calendar']);
        Route::get('/tasks', [TaskApiController::class, 'index']);
        Route::post('/tasks', [TaskApiController::class, 'store']);
        Route::get('/tasks/{taskId}', [TaskApiController::class, 'show'])->whereNumber('taskId');
        Route::match(['put', 'patch'], '/tasks/{taskId}', [TaskApiController::class, 'update'])->whereNumber('taskId');
        Route::delete('/tasks/{taskId}', [TaskApiController::class, 'destroy'])->whereNumber('taskId');
        Route::patch('/tasks/{taskId}/status', [TaskApiController::class, 'updateStatus'])->whereNumber('taskId');

        // Checklists
        Route::get('/projects/{project}/checklists', [ChecklistApiController::class, 'index'])->whereNumber('project');
        Route::post('/projects/{project}/checklists', [ChecklistApiController::class, 'store'])->whereNumber('project');
        Route::get('/projects/{project}/checklist-results', [ChecklistApiController::class, 'results'])->whereNumber('project');
        Route::get('/checklists/{checklist}', [ChecklistApiController::class, 'show'])->whereNumber('checklist');
        Route::match(['put', 'patch'], '/checklists/{checklist}', [ChecklistApiController::class, 'update'])->whereNumber('checklist');
        Route::delete('/checklists/{checklist}', [ChecklistApiController::class, 'destroy'])->whereNumber('checklist');
        Route::post('/checklists/{checklist}/items', [ChecklistItemApiController::class, 'store'])->whereNumber('checklist');
        Route::post('/checklist-items/reorder', [ChecklistItemApiController::class, 'reorder']);
        Route::match(['put', 'patch'], '/checklist-items/{item}', [ChecklistItemApiController::class, 'update'])->whereNumber('item');
        Route::delete('/checklist-items/{item}', [ChecklistItemApiController::class, 'destroy'])->whereNumber('item');
        Route::patch('/checklist-items/{item}/completion', [ChecklistItemApiController::class, 'complete'])->whereNumber('item');
        Route::put('/checklist-items/{item}/result', [ChecklistItemApiController::class, 'result'])->whereNumber('item');

        // Checklist templates
        Route::get('/checklist-templates', [ChecklistTemplateApiController::class, 'index']);
        Route::post('/checklist-templates', [ChecklistTemplateApiController::class, 'store']);
        Route::get('/checklist-templates/{id}', [ChecklistTemplateApiController::class, 'show'])->whereNumber('id');
        Route::match(['put', 'patch'], '/checklist-templates/{id}', [ChecklistTemplateApiController::class, 'update'])->whereNumber('id');
        Route::delete('/checklist-templates/{id}', [ChecklistTemplateApiController::class, 'destroy'])->whereNumber('id');

        // Supplies (primary) + nested create under project
        Route::get('/projects/{project}/supplies', [SupplyApiController::class, 'index'])->whereNumber('project');
        Route::post('/projects/{project}/supplies', [SupplyApiController::class, 'store'])->whereNumber('project');
        Route::get('/supplies/{supply}', [SupplyApiController::class, 'show'])->whereNumber('supply');
        Route::match(['put', 'patch'], '/supplies/{supply}', [SupplyApiController::class, 'update'])->whereNumber('supply');
        Route::delete('/supplies/{supply}', [SupplyApiController::class, 'destroy'])->whereNumber('supply');
        Route::patch('/supplies/{supply}/status', [SupplyApiController::class, 'updateStatus'])->whereNumber('supply');
        Route::post('/supplies/{supply}/send', [SupplyApiController::class, 'send'])->whereNumber('supply');
        Route::get('/supplies/{supply}/comments', [SupplyApiController::class, 'comments'])->whereNumber('supply');
        Route::post('/supplies/{supply}/comments', [SupplyApiController::class, 'storeComment'])->whereNumber('supply')->middleware('throttle:60,1');
        Route::post('/supplies/{supply}/percentage-proposals', [SupplyApiController::class, 'sendProposal'])->whereNumber('supply');
        Route::post('/supplies/{supply}/percentage-proposals/accept', [SupplyApiController::class, 'acceptProposal'])->whereNumber('supply');
        Route::post('/supplies/{supply}/percentage-proposals/reject', [SupplyApiController::class, 'rejectProposal'])->whereNumber('supply');
        Route::post('/supplies/{supply}/percentage-proposals/counter', [SupplyApiController::class, 'counterProposal'])->whereNumber('supply');
        Route::post('/supplies/{supply}/items', [SupplyItemApiController::class, 'store'])->whereNumber('supply');
        Route::match(['put', 'patch'], '/supply-items/{item}', [SupplyItemApiController::class, 'updateTop'])->whereNumber('item');
        Route::delete('/supply-items/{item}', [SupplyItemApiController::class, 'destroyTop'])->whereNumber('item');

        // Legacy supplier-orders aliases (compat)
        Route::get('/supplier-orders', [DesignerDataController::class, 'supplierOrders']);
        Route::get('/supplier-orders/{id}', [DesignerDataController::class, 'supplierOrder'])->whereNumber('id');
        Route::post('/supplier-orders/{supply}/offer/send', [SupplyApiController::class, 'sendProposal'])->whereNumber('supply');
        Route::post('/supplier-orders/{supply}/offer/accept', [SupplyApiController::class, 'acceptProposal'])->whereNumber('supply');
        Route::post('/supplier-orders/{supply}/offer/reject', [SupplyApiController::class, 'rejectProposal'])->whereNumber('supply');
        Route::post('/supplier-orders/{supply}/offer/counter', [SupplyApiController::class, 'counterProposal'])->whereNumber('supply');

        // Suppliers
        Route::get('/suppliers', [DesignerSupplierApiController::class, 'index']);
        Route::post('/suppliers', [DesignerSupplierApiController::class, 'store']);
        Route::get('/suppliers/{supplier}', [DesignerSupplierApiController::class, 'show'])->whereNumber('supplier');
        Route::match(['put', 'patch'], '/suppliers/{supplier}', [DesignerSupplierApiController::class, 'update'])->whereNumber('supplier');
        Route::delete('/suppliers/{supplier}', [DesignerSupplierApiController::class, 'destroy'])->whereNumber('supplier');
        Route::get('/suppliers/{supplier}/products', [DesignerSupplierApiController::class, 'products'])->whereNumber('supplier');
        Route::post('/suppliers/{supplier}/favorite', [DesignerSupplierApiController::class, 'toggleFavorite'])->whereNumber('supplier');
        Route::delete('/suppliers/{supplier}/favorite', [DesignerSupplierApiController::class, 'toggleFavorite'])->whereNumber('supplier');

        // Team
        Route::get('/team', [TeamApiController::class, 'show']);
        Route::get('/team/members', [TeamApiController::class, 'members']);
        Route::post('/team/invitations', [TeamApiController::class, 'invite'])->middleware('throttle:20,1');
        Route::get('/team/invitations', [TeamApiController::class, 'invitations']);
        Route::post('/team/members/create-account', [TeamApiController::class, 'createAccount'])->middleware('throttle:10,1');
        Route::patch('/team/members/{member}/role', [TeamApiController::class, 'changeRole'])->whereNumber('member');
        Route::delete('/team/members/{member}', [TeamApiController::class, 'removeMember'])->whereNumber('member');
        Route::post('/team/invitations/{invitation}/resend', [TeamApiController::class, 'resendInvitation'])->whereNumber('invitation')->middleware('throttle:20,1');
        Route::delete('/team/invitations/{invitation}', [TeamApiController::class, 'cancelInvitation'])->whereNumber('invitation');
        Route::get('/team/assignees', [TeamApiController::class, 'assignees']);

        // Deprecated passport objects + old templates (compat)
        Route::get('/objects', [DesignerDataController::class, 'objects']);
        Route::get('/objects/{id}', [DesignerDataController::class, 'object'])->whereNumber('id');
        Route::post('/objects', [DesignerCrudController::class, 'storeObject']);
        Route::match(['put', 'patch'], '/objects/{id}', [DesignerCrudController::class, 'updateObject'])->whereNumber('id');
        Route::delete('/objects/{id}', [DesignerCrudController::class, 'destroyObject'])->whereNumber('id');
        Route::get('/templates', [ChecklistTemplateApiController::class, 'index']);
        Route::post('/templates', [ChecklistTemplateApiController::class, 'store']);
        Route::delete('/templates/{id}', [ChecklistTemplateApiController::class, 'destroy'])->whereNumber('id');
    });

    // Кабинет поставщика — не расширять
    Route::middleware('deposit.paid')->group(function () {
        Route::get('/supplier/orders', [SupplierApiController::class, 'orders']);
        Route::get('/supplier/orders/{id}', [SupplierApiController::class, 'order'])->whereNumber('id');
        Route::post('/supplier/orders/{id}/offer/accept', [SupplierApiController::class, 'acceptOffer'])->whereNumber('id');
        Route::post('/supplier/orders/{id}/offer/reject', [SupplierApiController::class, 'rejectOffer'])->whereNumber('id');
        Route::post('/supplier/orders/{id}/offer/counter', [SupplierApiController::class, 'counterOffer'])->whereNumber('id');
    });
});
