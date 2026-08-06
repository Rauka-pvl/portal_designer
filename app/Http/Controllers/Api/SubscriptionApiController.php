<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelSubscriptionRequest;
use App\Http\Requests\Api\ChangePlanRequest;
use App\Http\Requests\Api\SubscriptionCheckoutRequest;
use App\Http\Requests\Api\UpdateSubscriptionPaymentMethodRequest;
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
                'comparison_feature_keys' => DesignerSubscription::comparisonFeatureKeys(),
                'trial_days' => DesignerSubscription::trialDays(),
                'trial_enabled' => DesignerSubscription::trialEnabled(),
                'period_days' => DesignerSubscription::periodDays(),
                'trial_requires_card' => DesignerSubscription::trialRequiresCard(),
                'payments_enabled' => DesignerSubscription::paymentsEnabled(),
                'promo_window_active' => DesignerSubscription::promoWindowActive(),
                'promo_period_days' => DesignerSubscription::promoPeriodDays(),
                'promo_valid_days' => DesignerSubscription::promoValidDays(),
                'promo_ends_at' => DesignerSubscription::promoEndsAt()?->toISOString(),
            ],
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => (new SubscriptionResource($request->user()))->resolve(),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $payments = DesignerSubscriptionPayment::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => [
                'payments' => SubscriptionPaymentResource::collection($payments)->resolve(),
                'has_real_payments' => DesignerSubscription::hasRealPayments($user),
                'show_payment_history' => DesignerSubscription::hasAccess($user)
                    && DesignerSubscription::hasRealPayments($user),
            ],
        ]);
    }

    public function checkout(SubscriptionCheckoutRequest $request): JsonResponse
    {
        $this->requireBillingPermission($request);
        $data = $request->validated();
        $method = DesignerSubscription::paymentsEnabled()
            ? $data['payment_method']
            : DesignerSubscription::METHOD_PROMO;
        $digits = preg_replace('/\D+/', '', (string) ($data['card_number'] ?? ''));
        $payment = DesignerSubscription::checkout(
            $request->user(),
            $data['plan'],
            $method,
            $data['promo_code'] ?? null,
            $digits && strlen($digits) >= 4 ? substr($digits, -4) : null,
            $data['card_expiry'] ?? null,
        );

        return response()->json([
            'data' => [
                'subscription' => (new SubscriptionResource($request->user()->fresh()))->resolve(),
                'payment' => (new SubscriptionPaymentResource($payment))->resolve(),
            ],
        ], 201);
    }

    public function changePlan(ChangePlanRequest $request): JsonResponse
    {
        $this->requireBillingPermission($request);
        $data = $request->validated();
        $user = $request->user();

        $catalog = app(\App\Services\Billing\PlanCatalog::class);
        if (DesignerSubscription::isCorporatePlanUser($user)
            && ($catalog->find($data['plan'])?->isIndividual() ?? false)
            && ! ($data['confirm_team_downgrade'] ?? false)) {
            throw ValidationException::withMessages([
                'plan' => [__('subscription.confirm_downgrade_from_corporate')],
            ]);
        }

        DesignerSubscription::changePlan($user, $data['plan']);

        return response()->json([
            'data' => (new SubscriptionResource($user->fresh()))->resolve(),
        ]);
    }

    public function updatePaymentMethod(UpdateSubscriptionPaymentMethodRequest $request): JsonResponse
    {
        $this->requireBillingPermission($request);
        DesignerSubscription::updatePaymentMethod(
            $request->user(),
            $request->validated('payment_method')
        );

        return response()->json([
            'data' => (new SubscriptionResource($request->user()->fresh()))->resolve(),
        ]);
    }

    public function renew(Request $request): JsonResponse
    {
        $this->requireBillingPermission($request);
        DesignerSubscription::resume($request->user());

        return response()->json([
            'data' => (new SubscriptionResource($request->user()->fresh()))->resolve(),
        ]);
    }

    public function resume(Request $request): JsonResponse
    {
        return $this->renew($request);
    }

    public function cancel(CancelSubscriptionRequest $request): JsonResponse
    {
        $this->requireBillingPermission($request);
        DesignerSubscription::cancel($request->user(), $request->validated('reason'));

        return response()->json([
            'data' => (new SubscriptionResource($request->user()->fresh()))->resolve(),
        ]);
    }

    private function requireBillingPermission(Request $request): void
    {
        abort_unless(AccountPermissions::canManageBilling($request->user()), 403);
    }
}
