<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use App\Support\ProductQr;
use App\Support\SupplierDeposit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductQrTest extends TestCase
{
    use RefreshDatabase;

    private function makeSupplierWithProduct(array $supplierAttrs = []): array
    {
        $user = User::factory()->create([
            'role' => 'supplier',
            'must_change_password' => false,
        ]);

        $supplier = Supplier::query()->create(array_merge([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_status' => 'active',
            'moderation_status' => 'approved',
            'account_status' => SupplierDeposit::ACCOUNT_ACTIVE,
            'guarantee_balance' => 100000,
            'deposit_activated_at' => now(),
        ], $supplierAttrs));

        $product = SupplierProduct::query()->create([
            'supplier_id' => $supplier->id,
            'name' => 'Ламинат Дуб',
            'sku' => 'LAM-QR-001',
            'category' => 'Полы',
            'price' => 5200,
            'unit' => 'м²',
        ]);

        return [$user, $supplier, $product];
    }

    public function test_supplier_can_load_qr_modal_data_and_token_is_stable(): void
    {
        [$user, $supplier, $product] = $this->makeSupplierWithProduct();

        $first = $this->actingAs($user)
            ->getJson(route('supplier.products.qr', ['productId' => $product->id]))
            ->assertOk()
            ->json();

        $this->assertTrue($first['ok']);
        $this->assertNotEmpty($first['url']);
        $token = $product->fresh()->qr_token;
        $this->assertNotEmpty($token);

        $second = $this->actingAs($user)
            ->getJson(route('supplier.products.qr', ['productId' => $product->id]))
            ->assertOk()
            ->json();

        $this->assertSame($token, $product->fresh()->qr_token);
        $this->assertSame($first['url'], $second['url']);
    }

    public function test_guest_scanning_qr_is_sent_to_login_with_intended(): void
    {
        [$user, $supplier, $product] = $this->makeSupplierWithProduct();
        ProductQr::ensureToken($product);

        $this->get(route('product.qr.resolve', ['token' => $product->qr_token]))
            ->assertRedirect(route('login'));

        $this->assertStringContainsString(
            '/q/'.$product->qr_token,
            (string) session('url.intended')
        );
    }

    public function test_designer_with_access_opens_existing_product_card(): void
    {
        [$supplierUser, $supplier, $product] = $this->makeSupplierWithProduct();
        ProductQr::ensureToken($product);

        $designer = User::factory()->create([
            'role' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(7),
        ]);

        $this->actingAs($designer)
            ->get(route('product.qr.resolve', ['token' => $product->qr_token]))
            ->assertRedirect(route('suppliers.products.show', [
                'supplierId' => $supplier->id,
                'productId' => $product->id,
            ]));
    }

    public function test_owner_supplier_opens_own_product_card(): void
    {
        [$user, $supplier, $product] = $this->makeSupplierWithProduct();
        ProductQr::ensureToken($product);

        $this->actingAs($user)
            ->get(route('product.qr.resolve', ['token' => $product->qr_token]))
            ->assertRedirect(route('supplier.products.show', ['productId' => $product->id]));
    }

    public function test_other_supplier_cannot_download_qr(): void
    {
        [$owner, $supplier, $product] = $this->makeSupplierWithProduct();
        ProductQr::ensureToken($product);

        $other = User::factory()->create([
            'role' => 'supplier',
            'must_change_password' => false,
        ]);
        Supplier::query()->create([
            'user_id' => $other->id,
            'name' => 'Other',
            'email' => $other->email,
            'profile_status' => 'active',
            'moderation_status' => 'approved',
            'account_status' => SupplierDeposit::ACCOUNT_ACTIVE,
            'guarantee_balance' => 100000,
            'deposit_activated_at' => now(),
        ]);

        $this->actingAs($other)
            ->get(route('supplier.products.qr', ['productId' => $product->id]))
            ->assertNotFound();
    }

    public function test_invalid_token_shows_unavailable_page(): void
    {
        $this->get(route('product.qr.resolve', ['token' => str_repeat('a', 32)]))
            ->assertNotFound();
    }

    public function test_reissue_invalidates_old_token(): void
    {
        [$user, $supplier, $product] = $this->makeSupplierWithProduct();
        ProductQr::ensureToken($product);
        $old = $product->fresh()->qr_token;

        $this->actingAs($user)
            ->postJson(route('supplier.products.qr.reissue', ['productId' => $product->id]))
            ->assertOk();

        $new = $product->fresh()->qr_token;
        $this->assertNotSame($old, $new);

        $this->get(route('product.qr.resolve', ['token' => $old]))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('product.qr.resolve', ['token' => $new]))
            ->assertRedirect(route('supplier.products.show', ['productId' => $product->id]));
    }

    public function test_svg_download_works(): void
    {
        [$user, $supplier, $product] = $this->makeSupplierWithProduct();

        $response = $this->actingAs($user)
            ->get(route('supplier.products.qr.download', ['productId' => $product->id, 'format' => 'svg']));

        $response->assertOk();
        $this->assertStringContainsString('image/svg+xml', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_renaming_product_does_not_break_qr_token(): void
    {
        [$user, $supplier, $product] = $this->makeSupplierWithProduct();
        ProductQr::ensureToken($product);
        $token = $product->fresh()->qr_token;

        $product->update(['name' => 'Новое название товара']);

        $this->assertSame($token, $product->fresh()->qr_token);
        $this->actingAs($user)
            ->get(route('product.qr.resolve', ['token' => $token]))
            ->assertRedirect(route('supplier.products.show', ['productId' => $product->id]));
    }
}
