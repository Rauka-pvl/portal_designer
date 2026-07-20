<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\SupplierGuaranteeLedgerEntry;
use App\Models\SupplierGuaranteePayment;
use App\Models\User;
use App\Support\SupplierDeposit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierDepositTest extends TestCase
{
    use RefreshDatabase;

    private function makeSupplierUser(array $supplierAttrs = []): User
    {
        $user = User::factory()->create([
            'role' => 'supplier',
            'must_change_password' => false,
        ]);

        Supplier::query()->create(array_merge([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_status' => 'draft',
            'moderation_status' => 'draft',
            'account_status' => SupplierDeposit::ACCOUNT_DEPOSIT_REQUIRED,
            'guarantee_balance' => 0,
        ], $supplierAttrs));

        return $user->fresh();
    }

    public function test_register_redirects_supplier_to_deposit_page(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Supplier',
            'email' => 'supplier-deposit@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'portal' => 'supplier',
        ]);

        $response->assertRedirect(route('supplier.deposit.index'));

        $user = User::query()->where('email', 'supplier-deposit@example.com')->first();
        $this->assertNotNull($user);
        $supplier = $user->supplierProfile;
        $this->assertNotNull($supplier);
        $this->assertSame(SupplierDeposit::ACCOUNT_DEPOSIT_REQUIRED, $supplier->account_status);
    }

    public function test_dashboard_redirects_unpaid_supplier_to_deposit(): void
    {
        $user = $this->makeSupplierUser();

        $this->actingAs($user)
            ->get(route('supplier.index'))
            ->assertRedirect(route('supplier.deposit.index'));
    }

    public function test_create_payment_and_double_submit_reuses_same_payment(): void
    {
        $user = $this->makeSupplierUser();

        $this->actingAs($user)
            ->post(route('supplier.deposit.create'), ['terms_accepted' => '1'])
            ->assertRedirect();

        $first = SupplierGuaranteePayment::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($first);
        $this->assertSame(SupplierGuaranteePayment::STATUS_PENDING, $first->status);
        $this->assertSame(SupplierDeposit::amount(), (int) $first->amount);

        $this->actingAs($user)
            ->post(route('supplier.deposit.create'), ['terms_accepted' => '1'])
            ->assertRedirect(route('supplier.deposit.checkout', ['payment' => $first->uuid]));

        $this->assertSame(1, SupplierGuaranteePayment::query()->where('user_id', $user->id)->count());
        $this->assertSame(
            SupplierDeposit::ACCOUNT_PAYMENT_PENDING,
            $user->fresh()->supplierProfile->account_status
        );
    }

    public function test_return_url_without_confirm_does_not_activate(): void
    {
        $user = $this->makeSupplierUser();
        $this->actingAs($user)->post(route('supplier.deposit.create'), ['terms_accepted' => '1']);
        $payment = SupplierGuaranteePayment::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('supplier.deposit.return', ['payment' => $payment->uuid, 'success' => 'true', 'paid' => '1']))
            ->assertOk();

        $supplier = $user->fresh()->supplierProfile;
        $this->assertNotSame(SupplierDeposit::ACCOUNT_ACTIVE, $supplier->account_status);
        $this->assertSame(0, (int) $supplier->guarantee_balance);
        $this->assertSame(SupplierGuaranteePayment::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_demo_confirm_activates_and_credits_balance_once(): void
    {
        $user = $this->makeSupplierUser();
        $this->actingAs($user)->post(route('supplier.deposit.create'), ['terms_accepted' => '1']);
        $payment = SupplierGuaranteePayment::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('supplier.deposit.confirm', ['payment' => $payment->uuid]))
            ->assertRedirect(route('supplier.deposit.return', ['payment' => $payment->uuid]));

        $supplier = $user->fresh()->supplierProfile;
        $this->assertSame(SupplierDeposit::ACCOUNT_ACTIVE, $supplier->account_status);
        $this->assertSame(SupplierDeposit::amount(), (int) $supplier->guarantee_balance);
        $this->assertSame(SupplierGuaranteePayment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame(1, SupplierGuaranteeLedgerEntry::query()->where('payment_id', $payment->id)->count());

        // Second confirm must not double-credit.
        $this->actingAs($user)
            ->post(route('supplier.deposit.confirm', ['payment' => $payment->uuid]))
            ->assertRedirect();

        $this->assertSame(SupplierDeposit::amount(), (int) $user->fresh()->supplierProfile->guarantee_balance);
        $this->assertSame(1, SupplierGuaranteeLedgerEntry::query()->where('payment_id', $payment->id)->count());

        $this->actingAs($user)
            ->get(route('supplier.index'))
            ->assertOk();
    }

    public function test_amount_mismatch_does_not_activate(): void
    {
        $user = $this->makeSupplierUser();
        $payment = SupplierDeposit::createPayment($user);
        $payment->amount = 1;
        $payment->save();

        $this->expectException(\RuntimeException::class);
        SupplierDeposit::confirmDemoPayment($payment->fresh(), $user);
    }

    public function test_currency_mismatch_does_not_activate(): void
    {
        $user = $this->makeSupplierUser();
        $payment = SupplierDeposit::createPayment($user);

        $this->expectException(\RuntimeException::class);
        SupplierDeposit::applySuccessfulPayment(
            $payment,
            $user,
            SupplierDeposit::amount(),
            'USD',
            'evt_currency_mismatch'
        );
    }

    public function test_cancelled_payment_allows_new_one(): void
    {
        $user = $this->makeSupplierUser();
        $this->actingAs($user)->post(route('supplier.deposit.create'), ['terms_accepted' => '1']);
        $payment = SupplierGuaranteePayment::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('supplier.deposit.cancel', ['payment' => $payment->uuid]))
            ->assertRedirect(route('supplier.deposit.index'));

        $this->assertSame(SupplierGuaranteePayment::STATUS_CANCELLED, $payment->fresh()->status);

        $this->actingAs($user)
            ->post(route('supplier.deposit.create'), ['terms_accepted' => '1'])
            ->assertRedirect();

        $this->assertSame(2, SupplierGuaranteePayment::query()->where('user_id', $user->id)->count());
    }

    public function test_designer_is_not_forced_into_deposit_flow(): void
    {
        $user = User::factory()->create([
            'role' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(7),
        ]);

        $this->actingAs($user)
            ->get(route('supplier.deposit.index'))
            ->assertRedirect(); // role middleware
    }
}
