<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FIX-13: Indexes على الأعمدة الأكثر استخداماً في الاستعلامات
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_invoices_status_created');
            $table->index('payment_method',          'idx_invoices_payment');
            $table->index('cashier_id',              'idx_invoices_cashier');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index('product_id', 'idx_invoice_items_product');
        });

        Schema::table('supplier_accounts', function (Blueprint $table) {
            $table->index(['supplier_id', 'created_at'], 'idx_supplier_accounts_supplier');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['product_id', 'created_at'], 'idx_stock_movements_product');
            $table->index('movement_type',              'idx_stock_movements_type');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index(['supplier_id', 'status'], 'idx_purchase_orders_supplier_status');
        });

        Schema::table('sales_returns', function (Blueprint $table) {
            $table->index(['status', 'return_date'], 'idx_sales_returns_status_date');
        });
    }

    public function down(): void
    {
        Schema::table('invoices',          fn($t) => $t->dropIndex('idx_invoices_status_created'));
        Schema::table('invoices',          fn($t) => $t->dropIndex('idx_invoices_payment'));
        Schema::table('invoices',          fn($t) => $t->dropIndex('idx_invoices_cashier'));
        Schema::table('invoice_items',     fn($t) => $t->dropIndex('idx_invoice_items_product'));
        Schema::table('supplier_accounts', fn($t) => $t->dropIndex('idx_supplier_accounts_supplier'));
        Schema::table('stock_movements',   fn($t) => $t->dropIndex('idx_stock_movements_product'));
        Schema::table('stock_movements',   fn($t) => $t->dropIndex('idx_stock_movements_type'));
        Schema::table('purchase_orders',   fn($t) => $t->dropIndex('idx_purchase_orders_supplier_status'));
        Schema::table('sales_returns',     fn($t) => $t->dropIndex('idx_sales_returns_status_date'));
    }
};
