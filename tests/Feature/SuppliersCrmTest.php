<?php

namespace Tests\Feature;

use App\Models\DesignerFavoriteSupplier;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuppliersCrmTest extends TestCase
{
    use RefreshDatabase;

    private function designer(): User
    {
        return User::factory()->create([
            'account_type' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(14),
        ]);
    }

    private function makeSupplier(User $designer, array $overrides = []): Supplier
    {
        return Supplier::query()->create(array_merge([
            'user_id' => $designer->id,
            'created_by_user_id' => $designer->id,
            'profile_status' => 'active',
            'account_status' => 'active',
            'moderation_status' => 'approved',
            'name' => 'ТОО Поставщик',
            'phone' => '+77061112233',
            'email' => 'supplier'.uniqid().'@example.com',
            'website' => 'https://supplier.kz',
            'city' => 'Астана',
            'sphere' => 'flooring',
            'brands' => ['BrandX'],
            'recommend' => false,
        ], $overrides));
    }

    public function test_suppliers_crm_page_loads_with_compact_toolbar_and_modes(): void
    {
        $user = $this->designer();
        $this->makeSupplier($user);

        $html = $this->actingAs($user)->get('/suppliers')->assertOk()->getContent();

        $this->assertStringContainsString('crm-suppliers-workspace', $html);
        $this->assertStringContainsString('suppliers-table-panel', $html);
        $this->assertStringContainsString('suppliers-cards-panel', $html);
        $this->assertStringContainsString('suppliers-filters-btn', $html);
        $this->assertStringContainsString('suppliers-filters-panel', $html);
        $this->assertStringContainsString('suppliers-filter-chips', $html);
        $this->assertStringContainsString(__('suppliers.create_supplier'), $html);
        $this->assertStringContainsString(__('suppliers.table'), $html);
        $this->assertStringContainsString(__('suppliers.cards'), $html);
        $this->assertStringContainsString(__('suppliers.filters'), $html);
        $this->assertStringContainsString('crm.suppliers.view', $html);
        $this->assertStringContainsString('data-view="table"', $html);
        $this->assertStringContainsString('data-view="cards"', $html);
        $this->assertStringContainsString('viewSupplier', $html);
        $this->assertStringContainsString('addOrderFromSupplier', $html);
        $this->assertStringContainsString('toggleFavorite', $html);
        $this->assertStringContainsString('editSupplier', $html);
        $this->assertStringContainsString('deleteSupplier', $html);
        $this->assertStringNotContainsString('name="type_filter"', $html);
        $this->assertStringNotContainsString('name="city_filter"', $html);
    }

    public function test_suppliers_payload_includes_rating_and_favorite_without_n_plus_one(): void
    {
        $user = $this->designer();
        $a = $this->makeSupplier($user, ['name' => 'Alpha Supply', 'city' => 'Алматы']);
        $b = $this->makeSupplier($user, ['name' => 'Beta Supply', 'city' => 'Астана', 'recommend' => true]);

        DesignerFavoriteSupplier::query()->create([
            'designer_user_id' => $user->id,
            'supplier_id' => $a->id,
        ]);

        DB::enableQueryLog();
        $html = $this->actingAs($user)->get('/suppliers')->assertOk()->getContent();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThan(25, count($queries), 'Index should avoid N+1 per supplier row');
        $this->assertStringContainsString('Alpha Supply', $html);
        $this->assertStringContainsString('Beta Supply', $html);
        $this->assertStringContainsString('window.allSuppliers', $html);
        $this->assertStringContainsString('"is_favorite":true', $html);
        $this->assertStringContainsString('"city":"\u0410\u043b\u043c\u0430\u0442\u044b"', $html);
        $this->assertStringContainsString('"recommend":true', $html);
        $this->assertStringContainsString('suppliers-empty', $html);
        $this->assertStringContainsString('crm-action-menu.js', $html);
        $this->assertStringContainsString('CrmActionMenu', $html);
        $this->assertStringContainsString(__('suppliers.no_ratings'), $html);
        $this->assertStringContainsString(__('suppliers.open_profile'), $html);
        $this->assertStringContainsString(__('suppliers.add_order'), $html);
    }

    public function test_toggle_favorite_still_works(): void
    {
        $user = $this->designer();
        $supplier = $this->makeSupplier($user, [
            'profile_status' => 'active',
            'moderation_status' => 'approved',
        ]);

        $res = $this->actingAs($user)->postJson('/suppliers/'.$supplier->id.'/toggle-favorite')
            ->assertOk()
            ->json();

        $this->assertTrue($res['success'] ?? false);
        $this->assertTrue((bool) ($res['is_favorite'] ?? false));
        $this->assertDatabaseHas('designer_favorite_suppliers', [
            'designer_user_id' => $user->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_owned_draft_supplier_is_visible_on_index(): void
    {
        $user = $this->designer();
        $this->makeSupplier($user, [
            'name' => 'Draft Mine',
            'profile_status' => 'draft',
            'moderation_status' => 'pending',
        ]);

        $html = $this->actingAs($user)->get('/suppliers')->assertOk()->getContent();
        $this->assertStringContainsString('Draft Mine', $html);
        $this->assertStringContainsString(__('moderation.pending'), $html);
    }

    public function test_crm_action_menu_script_is_portal_based(): void
    {
        $js = file_get_contents(public_path('js/crm-action-menu.js'));
        $this->assertNotFalse($js);
        $this->assertStringContainsString('crm-action-menu-root', $js);
        $this->assertStringContainsString('document.body.appendChild', $js);
        $this->assertStringContainsString('getBoundingClientRect', $js);
        $this->assertStringContainsString('preferUp', $js);
        $this->assertStringContainsString("position = 'fixed'", $js);
        $this->assertStringContainsString('ArrowDown', $js);
        $this->assertStringContainsString('Escape', $js);
        $this->assertStringContainsString('crm-action-menu--sheet', $js);
        $this->assertStringContainsString('onScrollClose', $js);
    }
}
