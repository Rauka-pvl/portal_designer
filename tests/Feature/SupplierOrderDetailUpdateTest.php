<?php

namespace Tests\Feature;

use App\Models\PassportObject;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierOrderDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function designerWithOrder(array $orderAttrs = []): array
    {
        $designer = User::factory()->create([
            'role' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(7),
        ]);

        $supplierUser = User::factory()->create([
            'role' => 'supplier',
            'must_change_password' => false,
        ]);

        $supplier = Supplier::query()->create([
            'user_id' => $supplierUser->id,
            'name' => 'Test Supplier',
            'email' => $supplierUser->email,
            'profile_status' => 'active',
            'moderation_status' => 'approved',
        ]);

        $client = \App\Models\Client::query()->create([
            'user_id' => $designer->id,
            'full_name' => 'Test Client',
            'client_type' => 'person',
            'phone' => '+77001112233',
            'email' => 'client@example.com',
            'status' => 'in_work',
        ]);

        $object = PassportObject::query()->create([
            'user_id' => $designer->id,
            'client_id' => $client->id,
            'city' => 'Almaty',
            'address' => 'Test st 1',
            'type' => 'apartment',
            'status' => 'in_work',
            'area' => 50,
            'latitude' => 43.2,
            'longitude' => 76.9,
            'moderation_status' => 'approved',
        ]);

        $project = Project::query()->create([
            'user_id' => $designer->id,
            'object_id' => $object->id,
            'name' => 'Test Project',
            'status' => 'in_progress',
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'planned_cost' => 0,
            'actual_cost' => 0,
        ]);

        $order = Supplier_orders::query()->create(array_merge([
            'user_id' => $designer->id,
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'status' => 'order_confirmed',
            'is_sent_to_supplier' => true,
            'offer_status' => Supplier_orders::OFFER_ACCEPTED,
            'summa' => 10000,
            'bonus_percent' => 5,
            'category' => 'plumbing',
            'date_planned' => now()->addDays(10)->toDateString(),
            'payment_date' => now()->addDays(5)->toDateString(),
        ], $orderAttrs));

        return compact('designer', 'supplier', 'supplierUser', 'project', 'order');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(Supplier_orders $order, Project $project, Supplier $supplier, array $overrides = []): array
    {
        return array_merge([
            'intent' => 'update',
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'status' => $order->status,
            'summa' => (int) $order->summa,
            'bonus_percent' => $order->bonus_percent !== null ? (float) $order->bonus_percent : null,
            'category' => (string) ($order->category ?? 'plumbing'),
            'date_planned' => optional($order->date_planned)->format('Y-m-d'),
            'payment_date' => optional($order->payment_date)->format('Y-m-d'),
        ], $overrides);
    }

    public function test_detail_update_does_not_reset_to_draft(): void
    {
        ['designer' => $designer, 'order' => $order, 'project' => $project, 'supplier' => $supplier] = $this->designerWithOrder();

        $this->actingAs($designer)
            ->putJson('/supplier-orders/'.$order->id, $this->updatePayload($order, $project, $supplier, [
                'status' => 'advance_payment',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();
        $this->assertTrue((bool) $order->is_sent_to_supplier);
        $this->assertSame('advance_payment', $order->status);
        $this->assertSame(Supplier_orders::OFFER_ACCEPTED, $order->offer_status);
        $this->assertEquals(5.0, (float) $order->bonus_percent);
    }

    public function test_data_change_notifies_supplier_once(): void
    {
        ['designer' => $designer, 'order' => $order, 'project' => $project, 'supplier' => $supplier, 'supplierUser' => $supplierUser] = $this->designerWithOrder();

        $this->actingAs($designer)
            ->putJson('/supplier-orders/'.$order->id, $this->updatePayload($order, $project, $supplier, [
                'summa' => 12500,
            ]))
            ->assertOk();

        $this->assertSame(1, UserNotification::query()
            ->where('user_id', $supplierUser->id)
            ->where('action_key', 'supplier_order_updated')
            ->where('related_order_id', $order->id)
            ->count());
    }

    public function test_status_only_change_does_not_notify_data_updated(): void
    {
        ['designer' => $designer, 'order' => $order, 'project' => $project, 'supplier' => $supplier, 'supplierUser' => $supplierUser] = $this->designerWithOrder();

        $this->actingAs($designer)
            ->putJson('/supplier-orders/'.$order->id, $this->updatePayload($order, $project, $supplier, [
                'status' => 'full_payment',
            ]))
            ->assertOk();

        $this->assertSame(0, UserNotification::query()
            ->where('user_id', $supplierUser->id)
            ->where('action_key', 'supplier_order_updated')
            ->count());

        $order->refresh();
        $this->assertSame('full_payment', $order->status);
    }

    public function test_bonus_percent_change_renegotiates_offer(): void
    {
        ['designer' => $designer, 'order' => $order, 'project' => $project, 'supplier' => $supplier, 'supplierUser' => $supplierUser] = $this->designerWithOrder();

        $this->actingAs($designer)
            ->putJson('/supplier-orders/'.$order->id, $this->updatePayload($order, $project, $supplier, [
                'bonus_percent' => 8,
            ]))
            ->assertOk()
            ->assertJsonPath('meta.renegotiated', true);

        $order->refresh();
        $this->assertSame(Supplier_orders::OFFER_PENDING_SUPPLIER, $order->offer_status);
        $this->assertEquals(8.0, (float) $order->bonus_percent);
        $this->assertSame(1, UserNotification::query()
            ->where('user_id', $supplierUser->id)
            ->where('action_key', 'order_offer')
            ->count());
    }

    public function test_detail_update_rejects_funnel_status_before_offer_accepted(): void
    {
        ['designer' => $designer, 'order' => $order, 'project' => $project, 'supplier' => $supplier] = $this->designerWithOrder([
            'status' => 'order_created',
            'offer_status' => Supplier_orders::OFFER_PENDING_SUPPLIER,
        ]);

        $this->actingAs($designer)
            ->putJson('/supplier-orders/'.$order->id, $this->updatePayload($order, $project, $supplier, [
                'status' => 'delivery_completed',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $order->refresh();
        $this->assertSame('order_created', $order->status);
        $this->assertSame(Supplier_orders::OFFER_PENDING_SUPPLIER, $order->offer_status);
    }
}
