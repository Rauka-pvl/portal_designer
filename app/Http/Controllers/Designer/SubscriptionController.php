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

        return view('designer.subscription.index', [
            'plans' => DesignerSubscription::plans(),
            'status' => DesignerSubscription::status($user),
            'isOnTrial' => DesignerSubscription::isOnTrial($user),
            'trialDaysLeft' => DesignerSubscription::trialDaysLeft($user),
            'canUseTrial' => DesignerSubscription::canUseTrial($user),
            'currentPlan' => $user->subscription_plan,
            'paymentMethod' => $user->subscription_payment_method,
            'accessEndsAt' => DesignerSubscription::accessEndsAt($user),
            'hasAccess' => $hasAccess,
            'cancelledAt' => $user->subscription_cancelled_at,
            'payments' => DesignerSubscriptionPayment::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get(),
            'locked' => ! $hasAccess,
        ]);
    }

    public function checkout(Request $request, string $plan): View|RedirectResponse
    {
        if (! DesignerSubscription::plan($plan)) {
            return redirect()->route('subscription.index');
        }

        $user = $request->user();

        return view('designer.subscription.checkout', [
            'planKey' => $plan,
            'plan' => DesignerSubscription::plans()[$plan],
            'canUseTrial' => DesignerSubscription::canUseTrial($user),
            'hasAccess' => DesignerSubscription::hasAccess($user),
            'locked' => ! DesignerSubscription::hasAccess($user),
        ]);
    }

    public function purchase(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'in:standard,pro'],
            'payment_method' => ['required', 'in:kaspi,card,promo'],
            'promo_code' => ['nullable', 'string', 'max:100'],
            'card_number' => ['nullable', 'string', 'max:32'],
            'card_expiry' => ['nullable', 'string', 'max:10'],
            'card_cvc' => ['nullable', 'string', 'max:4'],
        ]);

        $method = $data['payment_method'];
        $promo = $data['promo_code'] ?? null;

        // Если введён валидный промокод — оплата промо
        if (DesignerSubscription::isValidPromo($promo)) {
            $method = 'promo';
        }

        // Карта / Kaspi пока заглушки: без промо всё равно активируем (демо)
        $wasTrialEligible = DesignerSubscription::canUseTrial($request->user());

        DesignerSubscription::checkout(
            $request->user(),
            $data['plan'],
            $method,
            $promo
        );

        $message = $wasTrialEligible
            ? __('subscription.trial_started')
            : __('subscription.purchase_success');

        return redirect()
            ->route('dashboard')
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
        DesignerSubscription::cancel($request->user());

        return back()->with('success', __('subscription.cancelled'));
    }
}
