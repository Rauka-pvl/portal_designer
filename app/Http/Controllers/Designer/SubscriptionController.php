<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\DesignerSubscriptionPayment;
use App\Support\DesignerSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $hasAccess = DesignerSubscription::hasAccess($user);
        $status = DesignerSubscription::status($user);
        $currentPlan = $user->subscription_plan;
        $planData = $currentPlan ? DesignerSubscription::plan($currentPlan) : null;
        $accessEndsAt = DesignerSubscription::accessEndsAt($user);
        $nextChargeAt = DesignerSubscription::nextChargeAt($user);
        $nextChargeAmount = DesignerSubscription::nextChargeAmount($user);

        $payments = DesignerSubscriptionPayment::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (DesignerSubscriptionPayment $p) => $this->paymentRow($p));

        $hasRealPayments = DesignerSubscription::hasRealPayments($user);
        $isOnboarding = DesignerSubscription::needsOnboardingLayout($user);

        return view('designer.subscription.index', [
            'plans' => DesignerSubscription::plans(),
            'comparisonFeatures' => DesignerSubscription::comparisonFeatureKeys(),
            'status' => $status,
            'isOnTrial' => DesignerSubscription::isOnTrial($user),
            'trialDaysLeft' => DesignerSubscription::trialDaysLeft($user),
            'trialProgress' => DesignerSubscription::trialProgressPercent($user),
            'trialTotalDays' => DesignerSubscription::TRIAL_DAYS,
            'canUseTrial' => DesignerSubscription::canUseTrial($user),
            'trialRequiresCard' => DesignerSubscription::trialRequiresCard(),
            'currentPlan' => $currentPlan,
            'planPrice' => $planData ? (int) $planData['price'] : null,
            'paymentMethod' => $user->subscription_payment_method,
            'cardLast4' => DesignerSubscription::cardLast4($user),
            'cardExpiry' => DesignerSubscription::cardExpiry($user),
            'accessEndsAt' => $accessEndsAt,
            'accessEndsAtLabel' => DesignerSubscription::formatDate($accessEndsAt),
            'nextChargeAt' => $nextChargeAt,
            'nextChargeAtLabel' => DesignerSubscription::formatDate($nextChargeAt),
            'nextChargeAmount' => $nextChargeAmount,
            'nextChargeAmountLabel' => $nextChargeAmount !== null
                ? DesignerSubscription::formatMoney($nextChargeAmount)
                : null,
            'autoRenew' => DesignerSubscription::isAutoRenewEnabled($user),
            'primaryAction' => DesignerSubscription::primaryAction($user),
            'hasAccess' => $hasAccess,
            'isOnboarding' => $isOnboarding,
            'hasRealPayments' => $hasRealPayments,
            'showPaymentHistory' => $hasAccess && $hasRealPayments,
            'cancelledAt' => $user->subscription_cancelled_at,
            'billingName' => $user->name,
            'billingEmail' => $user->email,
            'payments' => $payments,
            'locked' => ! $hasAccess,
        ]);
    }

    public function checkout(Request $request, string $plan): View|RedirectResponse
    {
        if (! DesignerSubscription::plan($plan)) {
            return redirect()->route('subscription.index');
        }

        $user = $request->user();

        $hasAccess = DesignerSubscription::hasAccess($user);

        return view('designer.subscription.checkout', [
            'planKey' => $plan,
            'plan' => DesignerSubscription::plans()[$plan],
            'canUseTrial' => DesignerSubscription::canUseTrial($user),
            'trialTotalDays' => DesignerSubscription::TRIAL_DAYS,
            'trialRequiresCard' => DesignerSubscription::trialRequiresCard(),
            'hasAccess' => $hasAccess,
            'isOnboarding' => DesignerSubscription::needsOnboardingLayout($user),
            'locked' => ! $hasAccess,
            'checkoutStep' => 2,
        ]);
    }

    public function purchase(Request $request): RedirectResponse
    {
        $planKeys = array_keys(DesignerSubscription::plans());

        $data = $request->validate([
            'plan' => ['required', 'in:'.implode(',', $planKeys)],
            'payment_method' => ['required', 'in:kaspi,card,promo'],
            'promo_code' => ['nullable', 'string', 'max:100'],
            'card_number' => ['nullable', 'string', 'max:32'],
            'card_expiry' => ['nullable', 'string', 'max:10'],
            'card_cvc' => ['nullable', 'string', 'max:4'],
        ]);

        $method = $data['payment_method'];
        $promo = $data['promo_code'] ?? null;

        if (DesignerSubscription::isValidPromo($promo)) {
            $method = 'promo';
        }

        $cardDigits = preg_replace('/\D+/', '', (string) ($data['card_number'] ?? ''));
        $cardLast4 = $cardDigits && strlen($cardDigits) >= 4 ? substr($cardDigits, -4) : null;

        $user = $request->user();
        $wasTrialEligible = DesignerSubscription::canUseTrial($user);

        DesignerSubscription::checkout(
            $user,
            $data['plan'],
            $method,
            $promo,
            $cardLast4,
            $data['card_expiry'] ?? null
        );

        $message = $wasTrialEligible
            ? __('subscription.trial_started', ['days' => DesignerSubscription::TRIAL_DAYS])
            : __('subscription.purchase_success');

        // After first activation, send the designer into the unlocked cabinet.
        if ($wasTrialEligible || DesignerSubscription::hasAccess($user->fresh())) {
            return redirect()
                ->route('dashboard')
                ->with('success', $message);
        }

        return redirect()
            ->route('subscription.index')
            ->with('success', $message);
    }

    public function changePlan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'in:standard,pro'],
        ]);

        DesignerSubscription::changePlan($request->user(), $data['plan']);

        return back()->with('success', __('subscription.plan_changed'));
    }

    public function updatePayment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'in:kaspi,card'],
        ]);

        DesignerSubscription::updatePaymentMethod($request->user(), $data['payment_method']);

        return back()->with('success', __('subscription.payment_updated'));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'in:expensive,not_using,missing_features,tech_issues,other'],
        ]);

        DesignerSubscription::cancel($request->user(), $data['reason'] ?? null);

        $ends = DesignerSubscription::formatDate(DesignerSubscription::accessEndsAt($request->user()->fresh()));

        return back()->with('success', __('subscription.cancelled_until', ['date' => $ends ?? '—']));
    }

    public function resume(Request $request): RedirectResponse
    {
        DesignerSubscription::resume($request->user());

        return back()->with('success', __('subscription.resumed'));
    }

    private function paymentRow(DesignerSubscriptionPayment $p): array
    {
        $meta = is_array($p->meta) ? $p->meta : [];
        $method = (string) ($meta['payment_method'] ?? '');
        $isTrial = (bool) ($meta['is_trial'] ?? false) || $p->status === 'trial';
        $listPrice = isset($meta['list_price']) ? (int) $meta['list_price'] : null;

        $statusKey = match ((string) $p->status) {
            'trial' => 'trial',
            'pending' => 'pending',
            'failed' => 'failed',
            'refunded' => 'refunded',
            'cancelled' => 'cancelled',
            default => 'paid',
        };

        return [
            'id' => $p->id,
            'date' => DesignerSubscription::formatDate($p->created_at),
            'plan' => $p->plan,
            'plan_label' => __('subscription.plan_'.$p->plan),
            'period' => DesignerSubscription::formatDate($p->starts_at)
                .'–'.DesignerSubscription::formatDate($p->ends_at),
            'method' => $method,
            'method_label' => match ($method) {
                'kaspi' => __('subscription.pay_kaspi'),
                'card' => __('subscription.pay_card'),
                'promo' => __('subscription.pay_promo'),
                default => '—',
            },
            'amount' => (int) $p->amount,
            'amount_label' => DesignerSubscription::formatMoney((int) $p->amount),
            'list_price_label' => $listPrice !== null ? DesignerSubscription::formatMoney($listPrice) : null,
            'is_trial' => $isTrial,
            'status' => $statusKey,
            'status_label' => __('subscription.payment_status_'.$statusKey),
            'has_receipt' => ! $isTrial && (int) $p->amount > 0 && $statusKey === 'paid',
        ];
    }
}
