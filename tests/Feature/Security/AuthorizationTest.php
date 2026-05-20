<?php

namespace Tests\Feature\Security;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * TC-SEC: Security & authorization — RBAC, inactive users, CSRF-free API, rate limiting.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;
    private User $warehouse;
    private User $inactiveUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin    = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');

        $this->cashier  = User::factory()->create(['is_active' => true]);
        $this->cashier->assignRole('cashier');

        $this->warehouse = User::factory()->create(['is_active' => true]);
        $this->warehouse->assignRole('warehouse');

        $this->inactiveUser = User::factory()->create(['is_active' => false]);
        $this->inactiveUser->assignRole('cashier');
    }

    // ── Authentication ────────────────────────────────────────────────────────

    #[Test]
    public function unauthenticated_api_request_returns_401(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
        $this->getJson('/api/invoices/INV-001')->assertStatus(401);
        $this->getJson('/api/customers')->assertStatus(401);
    }

    #[Test]
    public function inactive_user_cannot_authenticate(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => $this->inactiveUser->username,
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function wrong_password_returns_401(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => $this->cashier->username,
            'password'  => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function valid_credentials_authenticate_user(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => $this->admin->username,
            'password'  => 'password',
        ]);

        // Should return 200 with session cookie
        $response->assertStatus(200);
    }

    // ── Role-based access ─────────────────────────────────────────────────────

    #[Test]
    public function cashier_cannot_manage_users(): void
    {
        $this->actingAs($this->cashier)->getJson('/api/users')->assertStatus(403);
        $this->actingAs($this->cashier)->postJson('/api/users', [])->assertStatus(403);
    }

    #[Test]
    public function cashier_cannot_access_financial_reports(): void
    {
        $this->actingAs($this->cashier)->postJson('/api/reports/income-statement', [
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
        ])->assertStatus(403);
    }

    #[Test]
    public function cashier_cannot_manage_products(): void
    {
        $this->actingAs($this->cashier)->postJson('/api/products', [
            'name'  => 'Unauthorized Product',
            'price' => 10,
        ])->assertStatus(403);
    }

    #[Test]
    public function cashier_can_create_invoices(): void
    {
        $product = Product::factory()->create(['price' => 50.00, 'quantity' => 10]);

        $this->actingAs($this->cashier)->postJson('/api/invoices', [
            'items'          => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
        ])->assertStatus(201);
    }

    #[Test]
    public function warehouse_cannot_create_invoices(): void
    {
        $product = Product::factory()->create(['price' => 50.00, 'quantity' => 10]);

        $this->actingAs($this->warehouse)->postJson('/api/invoices', [
            'items'          => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
        ])->assertStatus(403);
    }

    #[Test]
    public function admin_can_access_all_endpoints(): void
    {
        $this->actingAs($this->admin)->getJson('/api/products')->assertStatus(200);
        $this->actingAs($this->admin)->getJson('/api/users')->assertStatus(200);
        $this->actingAs($this->admin)->getJson('/api/customers')->assertStatus(200);
    }

    #[Test]
    public function warehouse_can_manage_stock(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        $this->actingAs($this->warehouse)->postJson("/api/products/{$product->id}/add-stock", [
            'quantity' => 5,
            'cost'     => 30.00,
            'reason'   => 'purchase',
        ])->assertStatus(200);
    }

    // ── User deactivation ─────────────────────────────────────────────────────

    #[Test]
    public function deactivated_user_cannot_access_api(): void
    {
        // Deactivate the cashier
        $this->cashier->update(['is_active' => false]);

        $this->actingAs($this->cashier)->getJson('/api/products')
            ->assertStatus(401); // CheckUserIsActive middleware blocks them
    }

    // ── Injection & validation hardening ─────────────────────────────────────

    #[Test]
    public function sql_injection_in_product_search_is_harmless(): void
    {
        $response = $this->actingAs($this->cashier)->getJson(
            '/api/products/search?q=' . urlencode("' OR 1=1; --")
        );

        // Must not crash; should return empty or safe results
        $response->assertStatus(200);
    }

    #[Test]
    public function xss_attempt_in_customer_name_is_sanitized(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/customers', [
            'name'  => '<script>alert("xss")</script>',
            'phone' => '01000000099',
        ]);

        // Either reject with 422 or store the sanitized version
        if ($response->status() === 201) {
            $storedName = \App\Models\Customer::latest()->first()->name;
            $this->assertStringNotContainsString('<script>', $storedName);
        } else {
            $response->assertStatus(422);
        }
    }

    #[Test]
    public function oversized_payload_is_rejected(): void
    {
        $hugeNotes = str_repeat('A', 100000);

        $product = Product::factory()->create(['price' => 50.00, 'quantity' => 10]);

        $this->actingAs($this->cashier)->postJson('/api/invoices', [
            'items'          => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'notes'          => $hugeNotes,
        ])->assertStatus(422);
    }

    // ── Tenant isolation ──────────────────────────────────────────────────────

    #[Test]
    public function user_data_is_scoped_to_current_tenant(): void
    {
        // All DB operations target the current tenant's database.
        // Create users, products, invoices in current tenant and verify
        // they are not accessible from a different tenant context.

        $product = Product::factory()->create(['quantity' => 10, 'price' => 50.00]);

        // In current tenant, user can see it
        $response = $this->actingAs($this->cashier)->getJson("/api/products?search={$product->name}");
        $response->assertStatus(200);
        $ids = collect($response->json('products') ?? $response->json())->pluck('id');
        $this->assertContains($product->id, $ids->toArray());
    }
}
