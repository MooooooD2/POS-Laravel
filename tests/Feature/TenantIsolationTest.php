<?php

namespace Tests\Feature;

use App\Http\Middleware\InitializeTenancyBySession;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Multi-Tenant Isolation Tests — three layers:
 *  Layer 1: Middleware behaviour (SQLite-safe)
 *  Layer 2: Permission-based isolation (SQLite-safe)
 *  Layer 3: group:integration — actual cross-tenant DB isolation (requires MySQL)
 *           Run: php artisan test --group=integration
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Redirect the 'tenant' DB connection to the same SQLite instance as 'sqlite'.
     *
     * The User model hardcodes $connection = 'tenant'. In a normal test run the
     * 'tenant' config points to MySQL (with DB_DATABASE=:memory:), which MySQL
     * rejects. By extending the DB manager we return the already-configured
     * sqlite connection, so RefreshDatabase's transaction wraps both at once.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $app['db']->extend('tenant', fn() => $app['db']->connection('sqlite'));

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    // ── Layer 1: Middleware behaviour ────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function middleware_skips_initialization_when_no_tenant_in_session(): void
    {
        $middleware = new InitializeTenancyBySession();
        $request    = Request::create('/api/dashboard-data', 'GET');
        $request->setLaravelSession(app('session')->driver('array'));

        $called = false;
        $middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('OK');
        });

        $this->assertTrue($called, 'Next middleware must be called when there is no tenant_id in session');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function middleware_removes_stale_session_when_tenant_is_deleted(): void
    {
        $request = Request::create('/api/dashboard-data', 'GET');
        $request->setLaravelSession(app('session')->driver('array'));
        $request->session()->put('tenant_id', 'nonexistent-uuid-9999');

        $middleware = new InitializeTenancyBySession();
        $middleware->handle($request, fn() => new Response('OK'));

        $this->assertFalse(
            $request->session()->has('tenant_id'),
            'Stale tenant_id must be removed from session when the tenant no longer exists'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_request_to_any_api_returns_401(): void
    {
        $endpoints = [
            ['GET',  '/api/products'],
            ['GET',  '/api/suppliers'],
            ['GET',  '/api/journal-entries'],
            ['GET',  '/api/dashboard-data'],
            ['GET',  '/api/users'],
            ['GET',  '/api/reports/stock'],
            ['GET',  '/api/audit-logs'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $this->json($method, $uri)->assertStatus(401);
        }
    }

    // ── Layer 2: Permission-based isolation ──────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function cashier_cannot_access_accounting_endpoints(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->createOne(['is_active' => true]);
        $cashier->assignRole('cashier');

        $this->actingAs($cashier)->getJson('/api/journal-entries')->assertStatus(403);
        $this->actingAs($cashier)->getJson('/api/accounts')->assertStatus(403);
        $this->actingAs($cashier)->getJson('/api/reports/balance-sheet')->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cashier_cannot_access_user_management(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->createOne(['is_active' => true]);
        $cashier->assignRole('cashier');

        $this->actingAs($cashier)->getJson('/api/users')->assertStatus(403);
        $this->actingAs($cashier)->getJson('/api/roles')->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cashier_cannot_manage_roles_or_permissions(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->createOne(['is_active' => true]);
        $cashier->assignRole('cashier');

        $this->actingAs($cashier)
            ->postJson('/api/roles', ['name' => 'hacker_role'])
            ->assertStatus(403);

        $this->actingAs($cashier)
            ->postJson('/api/roles/1/permissions', ['permissions' => ['manage_roles']])
            ->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cashier_cannot_access_sales_reports(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->createOne(['is_active' => true]);
        $cashier->assignRole('cashier');

        $this->actingAs($cashier)
            ->postJson('/api/reports/sales', ['start_date' => '2026-01-01', 'end_date' => '2026-12-31'])
            ->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function protected_admin_role_returns_404_on_sync_by_name(): void
    {
        /** @var User $admin */
        $admin = User::factory()->createOne(['is_active' => true]);
        $admin->assignRole('admin');

        // 'admin' is not a numeric Route model binding — returns 404 safely
        $this->actingAs($admin)
            ->postJson('/api/roles/admin/sync-permissions', ['permissions' => []])
            ->assertStatus(404);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inactive_user_cannot_access_protected_api(): void
    {
        /** @var User $inactive */
        $inactive = User::factory()->createOne(['is_active' => false]);
        $inactive->assignRole('cashier');

        $this->actingAs($inactive)->getJson('/api/products')->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function deactivated_user_loses_api_access_immediately(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne(['is_active' => true]);
        $user->assignRole('cashier');
        $user->update(['is_active' => false]);

        $this->actingAs($user)->getJson('/api/products')->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function password_is_never_exposed_in_user_api_responses(): void
    {
        /** @var User $admin */
        $admin = User::factory()->createOne(['is_active' => true]);
        $admin->assignRole('admin');
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/users')->assertStatus(200);

        $this->assertStringNotContainsString(
            '"password"',
            $response->content(),
            'Password field must never appear in API responses'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function products_are_visible_to_authenticated_warehouse_user(): void
    {
        /** @var User $warehouse */
        $warehouse = User::factory()->createOne(['is_active' => true]);
        $warehouse->assignRole('warehouse');

        /** @var Product $product */
        $product = Product::factory()->createOne(['name' => 'منتج مشترك', 'price' => 100]);

        $response = $this->actingAs($warehouse)->getJson('/api/products')->assertStatus(200);

        $ids = collect($response->json('data.products') ?? $response->json('products') ?? [])->pluck('id');
        $this->assertContains($product->id, $ids->all());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function audit_logs_endpoint_requires_accounting_permission(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->createOne(['is_active' => true]);
        $cashier->assignRole('cashier');

        $this->actingAs($cashier)->getJson('/api/audit-logs')->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function permissions_audit_report_requires_reports_permission(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->createOne(['is_active' => true]);
        $cashier->assignRole('cashier');

        $this->actingAs($cashier)->getJson('/api/reports/permissions-audit')->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function post_and_reverse_journal_entry_require_accounting_permission(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->createOne(['is_active' => true]);
        $cashier->assignRole('cashier');

        // SubstituteBindings resolves {entry} before permission middleware fires,
        // so a real entry must exist to get 403 rather than 404.
        $entry = \App\Models\JournalEntry::create([
            'entry_number' => 'JE-PERM-TEST-001',
            'entry_date'   => '2026-01-01',
            'description'  => 'Permission test entry',
        ]);

        $this->actingAs($cashier)->postJson("/api/journal-entries/{$entry->id}/post")->assertStatus(403);
        $this->actingAs($cashier)
            ->postJson("/api/journal-entries/{$entry->id}/reverse", ['description' => 'test'])
            ->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inventory_valuation_report_requires_reports_permission(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->createOne(['is_active' => true]);
        $cashier->assignRole('cashier');

        $this->actingAs($cashier)->getJson('/api/reports/inventory-valuation')->assertStatus(403);
    }

    // ── Layer 3: Integration tests (require MySQL) ───────────────────────────
    // Run: php artisan test --group=integration
    // These verify actual DB-level data isolation between two separate tenant databases.

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('integration')]
    public function tenant_a_products_are_not_visible_in_tenant_b_context(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Cross-tenant DB isolation requires MySQL. Run with --group=integration against a MySQL server.');
        }

        $tenantA = Tenant::create(['name' => 'Tenant A', 'code' => 'testa', 'is_active' => true]);
        $tenantB = Tenant::create(['name' => 'Tenant B', 'code' => 'testb', 'is_active' => true]);

        tenancy()->initialize($tenantA);
        Product::create(['name' => 'Exclusive Product A', 'price' => 100, 'cost_price' => 50, 'quantity' => 10]);
        tenancy()->end();

        tenancy()->initialize($tenantB);
        $count = Product::where('name', 'Exclusive Product A')->count();
        tenancy()->end();

        $this->assertEquals(0, $count, 'Tenant B must not see Tenant A products');
        $tenantA->delete();
        $tenantB->delete();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('integration')]
    public function users_from_different_tenants_share_no_data(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Cross-tenant DB isolation requires MySQL. Run with --group=integration against a MySQL server.');
        }

        $tenantA = Tenant::create(['name' => 'Store A', 'code' => 'storea', 'is_active' => true]);
        $tenantB = Tenant::create(['name' => 'Store B', 'code' => 'storeb', 'is_active' => true]);

        tenancy()->initialize($tenantA);
        User::create(['username' => 'user_a_exclusive', 'password' => bcrypt('pass'), 'full_name' => 'User A', 'is_active' => true]);
        tenancy()->end();

        tenancy()->initialize($tenantB);
        $found = User::where('username', 'user_a_exclusive')->exists();
        tenancy()->end();

        $this->assertFalse($found, 'Tenant B must not see Tenant A users');
        $tenantA->delete();
        $tenantB->delete();
    }
}
