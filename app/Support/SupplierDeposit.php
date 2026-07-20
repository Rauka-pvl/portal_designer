<?php

namespace App\Support;

use App\Models\Supplier;
use App\Models\SupplierGuaranteeLedgerEntry;
use App\Models\SupplierGuaranteePayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SupplierDeposit
{
    public const ACCOUNT_DEPOSIT_REQUIRED = 'deposit_required';

    public const ACCOUNT_PAYMENT_PENDING = 'payment_pending';

    public const ACCOUNT_ACTIVE = 'active';

    public static function amount(): int
    {
        return max(0, (int) config('supplier_deposit.amount', 100000));
    }

    public static function currency(): string
    {
        return (string) config('supplier_deposit.currency', 'KZT');
    }

    public static function isDemo(): bool
    {
        return (bool) config('supplier_deposit.demo', true);
    }

    public static function formatMoney(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' ₸';
    }

    public static function supplierFor(User $user): ?Supplier
    {
        if (($user->role ?? '') !== 'supplier') {
            return null;
        }

        return Supplier::query()->firstOrCreate(
            ['user_id' => (int) $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'profile_status' => 'draft',
                'moderation_status' => 'draft',
                'account_status' => self::ACCOUNT_DEPOSIT_REQUIRED,
                'guarantee_balance' => 0,
            ]
        );
    }

    public static function hasDepositAccess(User $user): bool
    {
        $supplier = self::supplierFor($user);
        if (! $supplier) {
            return false;
        }

        return $supplier->account_status === self::ACCOUNT_ACTIVE
            && (int) $supplier->guarantee_balance >= self::amount();
    }

    /** Deposit paid once — access to cabinet (moderation may still apply separately). */
    public static function isDepositPaid(User $user): bool
    {
        $supplier = self::supplierFor($user);
        if (! $supplier) {
            return false;
        }

        // Prefer a fresh read so middleware never trusts a stale relation.
        $status = Supplier::query()
            ->whereKey($supplier->id)
            ->value('account_status');

        return $status === self::ACCOUNT_ACTIVE;
    }

    public static function redirectRoute(User $user): string
    {
        return self::isDepositPaid($user) ? 'supplier.index' : 'supplier.deposit.index';
    }

    public static function latestPayment(Supplier $supplier): ?SupplierGuaranteePayment
    {
        return SupplierGuaranteePayment::query()
            ->where('supplier_id', $supplier->id)
            ->latest('id')
            ->first();
    }

    public static function activeReusablePayment(Supplier $supplier): ?SupplierGuaranteePayment
    {
        $payment = SupplierGuaranteePayment::query()
            ->where('supplier_id', $supplier->id)
            ->whereIn('status', [
                SupplierGuaranteePayment::STATUS_CREATED,
                SupplierGuaranteePayment::STATUS_PENDING,
            ])
            ->latest('id')
            ->first();

        if (! $payment) {
            return null;
        }

        if ($payment->expires_at && $payment->expires_at->isPast()) {
            self::expirePayment($payment);

            return null;
        }

        return $payment;
    }

    public static function createPayment(User $user, string $method = 'kaspi'): SupplierGuaranteePayment
    {
        $supplier = self::supplierFor($user);
        if (! $supplier) {
            throw new RuntimeException('Supplier profile missing');
        }

        if (self::isDepositPaid($user)) {
            throw new RuntimeException('Deposit already paid');
        }

        return DB::transaction(function () use ($user, $supplier, $method) {
            /** @var Supplier $supplier */
            $supplier = Supplier::query()->whereKey($supplier->id)->lockForUpdate()->firstOrFail();

            $existing = self::activeReusablePayment($supplier);
            if ($existing) {
                Log::info('supplier_deposit.reuse_payment', [
                    'payment_id' => $existing->id,
                    'supplier_id' => $supplier->id,
                    'user_id' => $user->id,
                ]);

                return $existing;
            }

            $amount = self::amount();
            $currency = self::currency();
            $uuid = (string) Str::uuid();
            $ttl = max(5, (int) config('supplier_deposit.session_ttl_minutes', 60));

            $payment = SupplierGuaranteePayment::query()->create([
                'user_id' => $user->id,
                'supplier_id' => $supplier->id,
                'uuid' => $uuid,
                'type' => config('supplier_deposit.payment_type', SupplierGuaranteePayment::TYPE_GUARANTEE_DEPOSIT),
                'amount' => $amount,
                'currency' => $currency,
                'status' => SupplierGuaranteePayment::STATUS_PENDING,
                'provider' => self::isDemo() ? 'demo' : 'pending_provider',
                'provider_payment_id' => 'demo_'.$uuid,
                'idempotency_key' => hash('sha256', 'deposit|'.$supplier->id.'|'.$uuid),
                'payment_url' => route('supplier.deposit.checkout', ['payment' => $uuid]),
                'expires_at' => now()->addMinutes($ttl),
                'meta' => [
                    'payment_method' => $method,
                    'demo' => self::isDemo(),
                ],
            ]);

            $supplier->account_status = self::ACCOUNT_PAYMENT_PENDING;
            $supplier->save();

            Log::info('supplier_deposit.created', [
                'payment_id' => $payment->id,
                'uuid' => $payment->uuid,
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return $payment;
        });
    }

    public static function expirePayment(SupplierGuaranteePayment $payment): void
    {
        if ($payment->status === SupplierGuaranteePayment::STATUS_EXPIRED) {
            return;
        }

        if (! in_array($payment->status, [
            SupplierGuaranteePayment::STATUS_CREATED,
            SupplierGuaranteePayment::STATUS_PENDING,
        ], true)) {
            return;
        }

        $payment->status = SupplierGuaranteePayment::STATUS_EXPIRED;
        $payment->save();

        Log::info('supplier_deposit.expired', [
            'payment_id' => $payment->id,
            'supplier_id' => $payment->supplier_id,
        ]);
    }

    public static function cancelPayment(SupplierGuaranteePayment $payment): void
    {
        if ($payment->isFinal()) {
            return;
        }

        $payment->status = SupplierGuaranteePayment::STATUS_CANCELLED;
        $payment->save();

        $supplier = Supplier::query()->find($payment->supplier_id);
        if ($supplier && $supplier->account_status === self::ACCOUNT_PAYMENT_PENDING && ! $supplier->deposit_activated_at) {
            $supplier->account_status = self::ACCOUNT_DEPOSIT_REQUIRED;
            $supplier->save();
        }

        Log::info('supplier_deposit.cancelled', [
            'payment_id' => $payment->id,
            'supplier_id' => $payment->supplier_id,
        ]);
    }

    /**
     * Demo "provider" confirmation — the only path that activates the account.
     * Validates ownership, amount, currency, idempotency inside a transaction.
     */
    public static function confirmDemoPayment(
        SupplierGuaranteePayment $payment,
        User $user,
        ?int $reportedAmount = null,
        ?string $reportedCurrency = null,
        ?string $eventId = null,
    ): SupplierGuaranteePayment {
        if (! self::isDemo()) {
            throw new RuntimeException('Demo confirmation disabled');
        }

        return self::applySuccessfulPayment(
            $payment,
            $user,
            $reportedAmount ?? $payment->amount,
            $reportedCurrency ?? $payment->currency,
            $eventId ?? ('demo_event_'.$payment->uuid),
            [
                'source' => 'demo_confirm',
                'confirmed_at' => now()->toIso8601String(),
            ]
        );
    }

    /**
     * Server-side success application (demo webhook / future real webhook).
     */
    public static function applySuccessfulPayment(
        SupplierGuaranteePayment $payment,
        ?User $actor,
        int $providerAmount,
        string $providerCurrency,
        string $eventId,
        array $payload = [],
    ): SupplierGuaranteePayment {
        return DB::transaction(function () use ($payment, $actor, $providerAmount, $providerCurrency, $eventId, $payload) {
            /** @var SupplierGuaranteePayment $locked */
            $locked = SupplierGuaranteePayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === SupplierGuaranteePayment::STATUS_PAID) {
                Log::info('supplier_deposit.webhook_duplicate', [
                    'payment_id' => $locked->id,
                    'event_id' => $eventId,
                ]);

                return $locked->fresh();
            }

            if ($locked->expires_at && $locked->expires_at->isPast()
                && in_array($locked->status, [
                    SupplierGuaranteePayment::STATUS_CREATED,
                    SupplierGuaranteePayment::STATUS_PENDING,
                ], true)) {
                $locked->status = SupplierGuaranteePayment::STATUS_EXPIRED;
                $locked->save();
                throw new RuntimeException('Payment session expired');
            }

            if (! in_array($locked->status, [
                SupplierGuaranteePayment::STATUS_CREATED,
                SupplierGuaranteePayment::STATUS_PENDING,
            ], true)) {
                throw new RuntimeException('Payment is not payable in status '.$locked->status);
            }

            if ($actor && (int) $locked->user_id !== (int) $actor->id) {
                Log::warning('supplier_deposit.ownership_mismatch', [
                    'payment_id' => $locked->id,
                    'actor_id' => $actor->id,
                    'owner_id' => $locked->user_id,
                ]);
                throw new RuntimeException('Payment does not belong to user');
            }

            $expectedAmount = self::amount();
            $expectedCurrency = self::currency();

            if ((int) $locked->amount !== $expectedAmount) {
                Log::warning('supplier_deposit.amount_mismatch_internal', [
                    'payment_id' => $locked->id,
                    'payment_amount' => $locked->amount,
                    'expected' => $expectedAmount,
                ]);
                throw new RuntimeException('Internal amount mismatch');
            }

            if ($providerAmount !== $expectedAmount) {
                Log::warning('supplier_deposit.amount_mismatch_provider', [
                    'payment_id' => $locked->id,
                    'provider_amount' => $providerAmount,
                    'expected' => $expectedAmount,
                ]);
                throw new RuntimeException('Provider amount mismatch');
            }

            if (strtoupper($providerCurrency) !== strtoupper($expectedCurrency)
                || strtoupper((string) $locked->currency) !== strtoupper($expectedCurrency)) {
                Log::warning('supplier_deposit.currency_mismatch', [
                    'payment_id' => $locked->id,
                    'provider_currency' => $providerCurrency,
                    'expected' => $expectedCurrency,
                ]);
                throw new RuntimeException('Currency mismatch');
            }

            $ledgerKey = 'ledger_payment_'.$locked->id;
            $existingLedger = SupplierGuaranteeLedgerEntry::query()
                ->where('idempotency_key', $ledgerKey)
                ->lockForUpdate()
                ->first();

            if ($existingLedger) {
                Log::info('supplier_deposit.ledger_already_exists', [
                    'payment_id' => $locked->id,
                    'ledger_id' => $existingLedger->id,
                ]);

                return $locked->fresh();
            }

            /** @var Supplier $supplier */
            $supplier = Supplier::query()->whereKey($locked->supplier_id)->lockForUpdate()->firstOrFail();

            $locked->status = SupplierGuaranteePayment::STATUS_PAID;
            $locked->paid_at = now();
            $locked->provider_event_id = $eventId;
            $locked->provider_payload = array_merge($locked->provider_payload ?? [], $payload, [
                'event_id' => $eventId,
                'provider_amount' => $providerAmount,
                'provider_currency' => $providerCurrency,
            ]);
            $locked->save();

            $newBalance = (int) $supplier->guarantee_balance + (int) $locked->amount;

            SupplierGuaranteeLedgerEntry::query()->create([
                'supplier_id' => $supplier->id,
                'user_id' => $locked->user_id,
                'payment_id' => $locked->id,
                'type' => SupplierGuaranteeLedgerEntry::TYPE_INITIAL_TOP_UP,
                'amount' => (int) $locked->amount,
                'currency' => $locked->currency,
                'balance_after' => $newBalance,
                'source' => 'supplier_payment',
                'status' => SupplierGuaranteeLedgerEntry::STATUS_COMPLETED,
                'idempotency_key' => $ledgerKey,
                'description' => 'Первоначальное пополнение гарантийного взноса',
                'meta' => [
                    'payment_uuid' => $locked->uuid,
                ],
            ]);

            $supplier->guarantee_balance = $newBalance;
            $supplier->account_status = self::ACCOUNT_ACTIVE;
            $supplier->deposit_activated_at = now();
            $supplier->save();

            Log::info('supplier_deposit.paid', [
                'payment_id' => $locked->id,
                'supplier_id' => $supplier->id,
                'amount' => $locked->amount,
                'balance_after' => $newBalance,
                'event_id' => $eventId,
            ]);

            return $locked->fresh();
        });
    }

    public static function refreshPaymentStatus(SupplierGuaranteePayment $payment): SupplierGuaranteePayment
    {
        if ($payment->expires_at
            && $payment->expires_at->isPast()
            && in_array($payment->status, [
                SupplierGuaranteePayment::STATUS_CREATED,
                SupplierGuaranteePayment::STATUS_PENDING,
            ], true)) {
            self::expirePayment($payment);

            return $payment->fresh();
        }

        return $payment->fresh();
    }
}
