<?php
namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FIX-3: اختبار صلاحية إنشاء المرتجعات
 */
class ReturnAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /** @test */
    public function user_without_returns_permission_cannot_create_return()
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('warehouse'); // warehouse ليس لديه view_returns

        $response = $this->actingAs($user)->postJson('/api/returns', [
            'invoice_id' => 1,
            'items'      => [['product_id' => 1, 'quantity' => 1]],
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function cashier_with_returns_permission_can_create_return()
    {
        $cashier = User::factory()->create(['is_active' => true]);
        $cashier->assignRole('cashier');

        $product = Product::factory()->create(['price' => 100, 'quantity' => 10]);
        $invoice = Invoice::factory()->create(['status' => 'completed']);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity'   => 5,
            'price'      => 100,
        ]);

        $response = $this->actingAs($cashier)->postJson('/api/returns', [
            'invoice_id' => $invoice->id,
            'items'      => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        // 201 created أو 422 (validation issue) — لكن ليس 403
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
