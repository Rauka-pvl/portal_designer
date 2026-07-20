<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\SupplierGuaranteeLedgerEntry;
use App\Models\SupplierGuaranteePayment;
use App\Support\SupplierDeposit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class DepositController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $supplier = SupplierDeposit::supplierFor($user);

        if (! $supplier) {
            abort(403);
        }

        if (SupplierDeposit::isDepositPaid($user)) {
            $payment = SupplierGuaranteePayment::query()
                ->where('supplier_id', $supplier->id)
                ->where('status', SupplierGuaranteePayment::STATUS_PAID)
                ->latest('id')
                ->first();

            return view('supplier.deposit.index', $this->viewData($supplier, $payment, 'paid'));
        }

        $payment = SupplierDeposit::latestPayment($supplier);
        if ($payment) {
            $payment = SupplierDeposit::refreshPaymentStatus($payment);
        }

        $state = $this->resolveState($payment);

        return view('supplier.deposit.index', $this->viewData($supplier, $payment, $state));
    }

    public function create(Request $request): RedirectResponse
    {
        $request->validate([
            'terms_accepted' => ['accepted'],
            'payment_method' => ['nullable', 'in:kaspi,card'],
        ]);

        $user = $request->user();

        if (SupplierDeposit::isDepositPaid($user)) {
            return redirect()->route('supplier.index');
        }

        try {
            $payment = SupplierDeposit::createPayment(
                $user,
                $request->input('payment_method', 'kaspi')
            );
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'deposit' => __('supplier_deposit.error_create'),
            ]);
        }

        return redirect()->route('supplier.deposit.checkout', ['payment' => $payment->uuid]);
    }

    public function checkout(Request $request, string $payment): View|RedirectResponse
    {
        $model = $this->findOwnedPayment($request, $payment);
        $model = SupplierDeposit::refreshPaymentStatus($model);
        $supplier = SupplierDeposit::supplierFor($request->user());

        if ($model->status === SupplierGuaranteePayment::STATUS_PAID) {
            return redirect()->route('supplier.deposit.index');
        }

        if ($model->status === SupplierGuaranteePayment::STATUS_EXPIRED) {
            return redirect()
                ->route('supplier.deposit.index')
                ->with('status', __('supplier_deposit.state_expired_title'));
        }

        return view('supplier.deposit.checkout', $this->viewData($supplier, $model, 'checkout'));
    }

    /**
     * Return URL from "provider" — does NOT activate the account.
     * Shows checking state; client may poll status.
     */
    public function returnFromProvider(Request $request, string $payment): View|RedirectResponse
    {
        $model = $this->findOwnedPayment($request, $payment);
        $model = SupplierDeposit::refreshPaymentStatus($model);
        $supplier = SupplierDeposit::supplierFor($request->user());

        // Ignore fake query flags from the client.
        $state = match ($model->status) {
            SupplierGuaranteePayment::STATUS_PAID => 'paid',
            SupplierGuaranteePayment::STATUS_FAILED => 'failed',
            SupplierGuaranteePayment::STATUS_CANCELLED => 'cancelled',
            SupplierGuaranteePayment::STATUS_EXPIRED => 'expired',
            default => 'checking',
        };

        return view('supplier.deposit.index', $this->viewData($supplier, $model, $state));
    }

    /**
     * Demo-only: simulate a signed provider webhook confirmation on the server.
     */
    public function confirmDemo(Request $request, string $payment): RedirectResponse
    {
        $model = $this->findOwnedPayment($request, $payment);

        if (! SupplierDeposit::isDemo()) {
            abort(404);
        }

        try {
            SupplierDeposit::confirmDemoPayment($model, $request->user());
        } catch (RuntimeException $e) {
            report($e);

            return redirect()
                ->route('supplier.deposit.return', ['payment' => $model->uuid])
                ->withErrors(['deposit' => __('supplier_deposit.error_confirm')]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('supplier.deposit.return', ['payment' => $model->uuid])
                ->withErrors(['deposit' => __('supplier_deposit.error_provider_temp')]);
        }

        return redirect()->route('supplier.deposit.return', ['payment' => $model->uuid]);
    }

    public function cancel(Request $request, string $payment): RedirectResponse
    {
        $model = $this->findOwnedPayment($request, $payment);
        SupplierDeposit::cancelPayment($model);

        return redirect()
            ->route('supplier.deposit.index')
            ->with('status', __('supplier_deposit.state_cancelled_title'));
    }

    public function status(Request $request, string $payment): JsonResponse
    {
        $model = $this->findOwnedPayment($request, $payment);
        $model = SupplierDeposit::refreshPaymentStatus($model);
        $supplier = SupplierDeposit::supplierFor($request->user());

        return response()->json([
            'success' => true,
            'status' => $model->status,
            'account_status' => $supplier?->account_status,
            'paid' => $model->status === SupplierGuaranteePayment::STATUS_PAID,
            'amount' => (int) $model->amount,
            'currency' => $model->currency,
            'paid_at' => optional($model->paid_at)?->toIso8601String(),
            'operation_id' => $model->uuid,
            'balance' => (int) ($supplier?->guarantee_balance ?? 0),
        ]);
    }

    public function retry(Request $request): RedirectResponse
    {
        if (SupplierDeposit::isDepositPaid($request->user())) {
            return redirect()->route('supplier.index');
        }

        return redirect()->route('supplier.deposit.index');
    }

    private function findOwnedPayment(Request $request, string $uuid): SupplierGuaranteePayment
    {
        $payment = SupplierGuaranteePayment::query()
            ->where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return $payment;
    }

    private function resolveState(?SupplierGuaranteePayment $payment): string
    {
        if (! $payment) {
            return 'ready';
        }

        return match ($payment->status) {
            SupplierGuaranteePayment::STATUS_PAID => 'paid',
            SupplierGuaranteePayment::STATUS_PENDING, SupplierGuaranteePayment::STATUS_CREATED => 'pending',
            SupplierGuaranteePayment::STATUS_FAILED => 'failed',
            SupplierGuaranteePayment::STATUS_CANCELLED => 'cancelled',
            SupplierGuaranteePayment::STATUS_EXPIRED => 'expired',
            default => 'ready',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData($supplier, ?SupplierGuaranteePayment $payment, string $state): array
    {
        $ledger = null;
        if ($payment && $payment->status === SupplierGuaranteePayment::STATUS_PAID) {
            $ledger = SupplierGuaranteeLedgerEntry::query()
                ->where('payment_id', $payment->id)
                ->first();
        }

        $amount = SupplierDeposit::amount();

        return [
            'supplier' => $supplier,
            'payment' => $payment,
            'state' => $state,
            'amount' => $amount,
            'amountLabel' => SupplierDeposit::formatMoney($amount),
            'currency' => SupplierDeposit::currency(),
            'isDemo' => SupplierDeposit::isDemo(),
            'ledger' => $ledger,
            'supportEmail' => config('supplier_deposit.support_email'),
            'accountStatus' => $supplier?->account_status,
            'guaranteeBalance' => (int) ($supplier?->guarantee_balance ?? 0),
            'guaranteeBalanceLabel' => SupplierDeposit::formatMoney((int) ($supplier?->guarantee_balance ?? 0)),
            'isOnboarding' => $supplier && $supplier->account_status !== SupplierDeposit::ACCOUNT_ACTIVE,
        ];
    }
}
