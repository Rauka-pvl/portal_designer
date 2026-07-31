<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelSubscriptionRequest;
use App\Http\Requests\Api\ChangePlanRequest;
use App\Http\Requests\Api\SubscriptionCheckoutRequest;
use App\Http\Resources\PlanResource;
use App\Http\Resources\SubscriptionPaymentResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\DesignerSubscriptionPayment;
use App\Support\AccountPermissions;
use App\Support\DesignerSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubscriptionApiController extends Controller
{
    public function plans(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'plans' => PlanResource::collection(collect(DesignerSubscription::plans())->values())->resolve(),
            ],
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => (new SubscriptionResource($request->user()))->resolve()]);
    }

    public function history(Request $request): JsonResponse
    {
        $payments = DesignerSubscriptionPayment::query()->where('user_id', $request->user()->id)->latest('id')->limit(20)->get();

        return response()->json(['data' => ['payments' => SubscriptionPaymentResource::collection($payments)->resolve()]]);
    }

    public function checkout(SubscriptionCheckoutRequest $request): JsonResponse
    {
        $this->requireBillingPermission($request);
        $data = $request->validated();
        $digits = preg_replace('/\D+/', '', (string) ($data['card_number'] ?? ''));
        $payment = DesignerSubscription::checkout(
            $request->user(), $data['plan'], $data['payment_method'], $data['promo_code'] ?? null,
            $digits && strlen($digits) >= 4 ? substr($digits, -4) : null, $data['card_expiry'] ?? null,
        );

        return response()->json(['data' => [
            'subscription' => (new SubscriptionResource($request->user()->fresh()))->resolve(),
            'payment' => (new SubscriptionPaymentResource($payment))->resolve(),
        ]], 201);
    }

    public function changePlan(ChangePlanRequest $request): JsonResponse
    {
        $this->requireBillingPermission($request);
        $data = $request->validated();
        $user = $request->user();
        if ($user->subscription_plan === DesignerSubscription::PLAN_CORPORATE
            && in_array($data['plan'], [DesignerSubscription::PLAN_STANDARD, DesignerSubscription::PLAN_PRO], true)
            && ! ($data['confirm_team_downgrade'] ?? false)) {
            throw ValidationException::withMessages(['plan' => [__('subscription.confirm_downgrade_from_corporate')]]);
        }
        DesignerSubscription::changePlan($user, $data['plan']);

        return response()->json(['data' => new SubscriptionResource($user->fresh())]);
    }

    public function renew(Request $request): JsonResponse
    {
        $this->requireBillingPermission($request);
        DesignerSubscription::resume($request->user());

        return response()->json(['data' => new SubscriptionResource($request->user()->fresh())]);
    }

    public function resume(Request $request): JsonResponse
    {
        return $this->renew($request);
    }

    public function cancel(CancelSubscriptionRequest $request): JsonResponse
    {
        $this->requireBillingPermission($request);
        DesignerSubscription::cancel($request->user(), $request->validated('reason'));

        return response()->json(['data' => new SubscriptionResource($request->user()->fresh())]);
    }

    private function requireBillingPermission(Request $request): void
    {
        abort_unless(AccountPermissions::canManageBilling($request->user()), 403);
    }
}
