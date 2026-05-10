<?php
namespace Tests\Unit;

use App\Models\Product;
use App\Models\Setting;
use App\Services\InvoiceService;
use App\Services\StockService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function discount_cannot_exceed_total()
    {
        $product = Product::factory()->create(['price' => 100.00, 'quantity' => 5]);

        $service  = app(InvoiceService::class);
        $this->actingAs(\App\Models\User::factory()->create());

        // خصم أكبر من الإجمالي — يجب أن يرفضه النظام
        $this->expectException(\Exception::class);

        $service->createInvoice([
            'items'          => [['product_id' => $product->id, 'quantity' => 1]],
            'discount'       => 999, // أكبر من الحد المسموح
            'payment_method' => 'cash',
        ]);
    }
}
