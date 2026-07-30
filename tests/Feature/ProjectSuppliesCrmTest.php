<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use App\Models\SupplierProduct;
use App\Models\User;
use App\Services\Crm\PipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSuppliesCrmTest extends TestCase
{
    use RefreshDatabase;

    private function designer(): User
    {
        return User::factory()->create([
            'role' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(14),
        ]);
    }

    private function seedProject(User $user): Project
    {
        $client = Client::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Клиент Поставок',
            'client_type' => 'person',
            'phone' => '+77001110000',
            'email' => 'supply-client@example.com',
            'status' => 'new',
        ]);

        return Project::query()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'name' => 'Проект для поставок',
            'status' => ProjectStatus::ContractNegotiation->value,
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'planned_cost' => 0,
            'actual_cost' => 0,
            'moderation_status' => 'approved',
        ]);
    }

    private function seedSupplier(User $user): Supplier
    {
        return Supplier::query()->create([
            'user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'name' => 'Тест Поставщик',
            'profile_status' => 'active',
            'moderation_status' => 'approved',
        ]);
    }

    public function test_crm_page_includes_supply_modals_and_no_project_client_selects(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $html = $this->actingAs($user)->get('/projects')->assertOk()->getContent();

        $this->assertStringContainsString('id="supply-modal-root"', $html);
        $this->assertStringContainsString('id="supply-catalog-root"', $html);
        $this->assertStringContainsString('id="supply-form"', $html);
        $this->assertStringContainsString('name="project_id"', $html);
        $this->assertStringContainsString('supply-ctx-project', $html);
        $this->assertStringNotContainsString('id="order_project_id"', $html);
        $this->assertStringContainsString('CrmSupplies', $html);
        $this->assertStringContainsString(__('projects.supplies_empty_title'), $html);
    }

    public function test_can_create_draft_supply_for_owned_project(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $project = $this->seedProject($user);
        $supplier = $this->seedSupplier($user);

        $response = $this->actingAs($user)->postJson('/supplier-orders', [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'summa' => 150000,
            'date_planned' => now()->addDays(7)->toDateString(),
            'bonus_percent' => 5,
            'send_to_supplier' => 0,
            'action' => 'save',
            'product_items' => json_encode([]),
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('supplier_orders', [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'status' => 'draft',
            'is_sent_to_supplier' => 0,
            'summa' => 150000,
        ]);
    }

    public function test_cannot_create_supply_for_foreign_project(): void
    {
        $owner = $this->designer();
        $intruder = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $owner->id);
        app(PipelineService::class)->ensureDefaultsForUser((int) $intruder->id);

        $project = $this->seedProject($owner);
        $supplier = $this->seedSupplier($intruder);

        $this->actingAs($intruder)->postJson('/supplier-orders', [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'summa' => 1000,
            'date_planned' => now()->toDateString(),
            'send_to_supplier' => 0,
            'action' => 'save',
        ])->assertStatus(422);
    }

    public function test_send_to_supplier_sets_offer_pending(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $project = $this->seedProject($user);
        $supplier = $this->seedSupplier($user);

        $this->actingAs($user)->postJson('/supplier-orders', [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'summa' => 200000,
            'date_planned' => now()->addDays(3)->toDateString(),
            'bonus_percent' => 7,
            'send_to_supplier' => 1,
            'action' => 'send',
        ])->assertOk()->assertJsonPath('success', true);

        $order = Supplier_orders::query()->where('project_id', $project->id)->first();
        $this->assertNotNull($order);
        $this->assertTrue((bool) $order->is_sent_to_supplier);
        $this->assertSame('pending_supplier', $order->offer_status);
        $this->assertSame('order_created', $order->status);
    }

    public function test_products_json_returns_only_selected_supplier_products(): void
    {
        $user = $this->designer();
        $supplierA = $this->seedSupplier($user);
        $supplierB = Supplier::query()->create([
            'user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'name' => 'Другой поставщик',
            'profile_status' => 'active',
            'moderation_status' => 'approved',
        ]);

        SupplierProduct::query()->create([
            'supplier_id' => $supplierA->id,
            'name' => 'Товар A',
            'price' => 1000,
            'unit' => 'шт',
        ]);
        SupplierProduct::query()->create([
            'supplier_id' => $supplierB->id,
            'name' => 'Товар B',
            'price' => 2000,
            'unit' => 'шт',
        ]);

        $json = $this->actingAs($user)
            ->getJson('/suppliers/'.$supplierA->id.'/products.json')
            ->assertOk()
            ->json();

        $this->assertTrue($json['success']);
        $this->assertCount(1, $json['data']);
        $this->assertSame('Товар A', $json['data'][0]['name']);
    }

    public function test_supplier_order_show_html_redirects_into_project_crm(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $project = $this->seedProject($user);
        $supplier = $this->seedSupplier($user);

        $order = Supplier_orders::query()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'is_sent_to_supplier' => false,
            'summa' => 5000,
            'category' => '',
            'mark' => '',
            'room' => '',
            'date_planned' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get('/supplier-orders/'.$order->id)
            ->assertRedirect(route('projects.index', [
                'open' => $project->id,
                'tab' => 'supplies',
                'supply' => $order->id,
                'section' => null,
            ]));
    }

    public function test_project_payload_includes_summa_not_zero_amount_bug(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $project = $this->seedProject($user);
        $supplier = $this->seedSupplier($user);

        Supplier_orders::query()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'is_sent_to_supplier' => false,
            'summa' => 4444,
            'category' => '',
            'mark' => '',
            'room' => '',
            'date_planned' => now()->toDateString(),
            'bonus_percent' => 3,
            'product_items' => [['product_id' => 1, 'name' => 'X', 'qty' => 2, 'price' => 10, 'unit' => 'шт']],
        ]);

        $payload = $this->actingAs($user)->getJson('/projects/'.$project->id)->assertOk()->json();
        $order = $payload['supplier_orders'][0] ?? null;
        $this->assertNotNull($order);
        $this->assertSame(4444, (int) $order['summa']);
        $this->assertSame(4444, (int) $order['amount']);
        $this->assertSame(1, (int) $order['products_count']);
        $this->assertCount(1, $order['product_items'] ?? []);
    }

    public function test_supplies_tab_renders_exclusive_view_mode_markup(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $html = $this->actingAs($user)->get('/projects')->assertOk()->getContent();

        $this->assertStringContainsString('id="ov-supplies-kanban"', $html);
        $this->assertStringContainsString('id="ov-supplies-list"', $html);
        $this->assertStringContainsString('crm-supply-list is-hidden', $html);
        $this->assertStringContainsString('data-sview="kanban"', $html);
        $this->assertStringContainsString('data-sview="list"', $html);
        $this->assertStringContainsString('project_supplies_view_mode', $html);
        $this->assertStringContainsString('project_supplies_search', $html);
        $this->assertStringContainsString('project_supplies_status_filter', $html);
        $this->assertStringContainsString('function setSupplyView', $html);
        $this->assertStringContainsString('function productsCount', $html);
        $this->assertStringContainsString('.crm-supply-board.is-hidden', $html);
        $this->assertStringContainsString('display: none !important', $html);
        $this->assertStringContainsString(__('projects.supplies_column_empty'), $html);
        $this->assertStringContainsString(__('projects.supplies_empty_title'), $html);
        $this->assertStringContainsString(__('projects.supplies_empty_body'), $html);

        // Default markup: kanban visible, list hidden — never both active in HTML shell.
        $this->assertMatchesRegularExpression(
            '/id="ov-supplies-kanban"\s+class="crm-supply-board"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/id="ov-supplies-list"\s+class="crm-supply-list is-hidden"/',
            $html
        );

        // Conditional rendering clears the inactive view DOM.
        $this->assertStringContainsString('list.innerHTML = \'\'', $html);
        $this->assertStringContainsString('kanban.innerHTML = \'\'', $html);
        $this->assertStringContainsString("if (state.view === 'list') renderListView(p);", $html);
        $this->assertStringContainsString('else renderKanban(p);', $html);

        // DnD only attached inside kanban renderer; list mode clears kanban first.
        $this->assertStringContainsString('function renderKanban', $html);
        $this->assertStringContainsString('function renderListView', $html);
        $this->assertStringContainsString('el.draggable = true', $html);
        $this->assertStringContainsString('kanban.classList.add(\'is-hidden\'); kanban.innerHTML = \'\'', $html);
    }

    public function test_project_payload_products_count_matches_product_items(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $project = $this->seedProject($user);
        $supplier = $this->seedSupplier($user);

        $items = [
            ['product_id' => 11, 'name' => 'Стул', 'qty' => 1, 'price' => 3000, 'unit' => 'шт'],
            ['product_id' => 12, 'name' => 'Стол', 'qty' => 1, 'price' => 3666, 'unit' => 'шт'],
        ];

        Supplier_orders::query()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'is_sent_to_supplier' => false,
            'summa' => 6666,
            'category' => '',
            'mark' => '',
            'room' => '',
            'date_planned' => now()->addDay()->toDateString(),
            'bonus_percent' => 6,
            'product_items' => $items,
        ]);

        // Manual summa without products still reports 0 items (real empty relation, not a UI bug).
        Supplier_orders::query()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'is_sent_to_supplier' => false,
            'summa' => 6666,
            'category' => '',
            'mark' => '',
            'room' => '',
            'date_planned' => now()->addDays(2)->toDateString(),
            'bonus_percent' => 6,
            'product_items' => [],
        ]);

        $payload = $this->actingAs($user)->getJson('/projects/'.$project->id)->assertOk()->json();
        $orders = $payload['supplier_orders'] ?? [];
        $this->assertCount(2, $orders);

        $withItems = collect($orders)->firstWhere('products_count', 2);
        $emptyItems = collect($orders)->firstWhere('products_count', 0);

        $this->assertNotNull($withItems);
        $this->assertSame(6666, (int) $withItems['summa']);
        $this->assertCount(2, $withItems['product_items']);
        $this->assertSame('Тест Поставщик', $withItems['supplier_name']);

        $this->assertNotNull($emptyItems);
        $this->assertSame(6666, (int) $emptyItems['summa']);
        $this->assertSame([], $emptyItems['product_items']);
    }

    public function test_supplier_order_json_payload_includes_product_items(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $project = $this->seedProject($user);
        $supplier = $this->seedSupplier($user);

        $order = Supplier_orders::query()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'is_sent_to_supplier' => false,
            'summa' => 6666,
            'category' => '',
            'mark' => '',
            'room' => '',
            'date_planned' => now()->toDateString(),
            'product_items' => [
                ['product_id' => 1, 'name' => 'A', 'qty' => 1, 'price' => 2000, 'unit' => 'шт'],
                ['product_id' => 2, 'name' => 'B', 'qty' => 1, 'price' => 4666, 'unit' => 'шт'],
            ],
        ]);

        $data = $this->actingAs($user)
            ->getJson('/supplier-orders/'.$order->id)
            ->assertOk()
            ->json();

        $this->assertSame(2, (int) ($data['products_count'] ?? 0));
        $this->assertCount(2, $data['product_items'] ?? []);
        $this->assertSame(6666, (int) ($data['summa'] ?? 0));
    }
}
