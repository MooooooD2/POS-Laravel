<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Check if a named index exists on a table. */
    private function hasIndex(string $table, string $index): bool
    {
        return !empty(DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$index]
        ));
    }

    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!$this->hasIndex('invoices', 'idx_invoices_status_created')) {
                $table->index(['status', 'created_at'], 'idx_invoices_status_created');
            }
            if (!$this->hasIndex('invoices', 'idx_invoices_payment')) {
                $table->index('payment_method', 'idx_invoices_payment');
            }
            // cashier_id already has an index from the FK (invoices_cashier_id_foreign).
            // Adding a second index on the same column causes problems on rollback.
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            if (!$this->hasIndex('invoice_items', 'idx_invoice_items_product')) {
                $table->index('product_id', 'idx_invoice_items_product');
            }
        });

        Schema::table('supplier_accounts', function (Blueprint $table) {
            if (!$this->hasIndex('supplier_accounts', 'idx_supplier_accounts_supplier')) {
                $table->index(['supplier_id', 'created_at'], 'idx_supplier_accounts_supplier');
            }
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            if (!$this->hasIndex('stock_movements', 'idx_stock_movements_product')) {
                $table->index(['product_id', 'created_at'], 'idx_stock_movements_product');
            }
            if (!$this->hasIndex('stock_movements', 'idx_stock_movements_type')) {
                $table->index('movement_type', 'idx_stock_movements_type');
            }
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!$this->hasIndex('purchase_orders', 'idx_purchase_orders_supplier_status')) {
                $table->index(['supplier_id', 'status'], 'idx_purchase_orders_supplier_status');
            }
        });

        Schema::table('sales_returns', function (Blueprint $table) {
            if (!$this->hasIndex('sales_returns', 'idx_sales_returns_status_date')) {
                $table->index(['status', 'return_date'], 'idx_sales_returns_status_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if ($this->hasIndex('invoices', 'idx_invoices_status_created')) {
                $table->dropIndex('idx_invoices_status_created');
            }
            if ($this->hasIndex('invoices', 'idx_invoices_payment')) {
                $table->dropIndex('idx_invoices_payment');
            }
        });
        Schema::table('invoice_items', function (Blueprint $table) {
            if ($this->hasIndex('invoice_items', 'idx_invoice_items_product')) {
                $table->dropIndex('idx_invoice_items_product');
            }
        });
        Schema::table('supplier_accounts', function (Blueprint $table) {
            if ($this->hasIndex('supplier_accounts', 'idx_supplier_accounts_supplier')) {
                $table->dropIndex('idx_supplier_accounts_supplier');
            }
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            if ($this->hasIndex('stock_movements', 'idx_stock_movements_product')) {
                $table->dropIndex('idx_stock_movements_product');
            }
            if ($this->hasIndex('stock_movements', 'idx_stock_movements_type')) {
                $table->dropIndex('idx_stock_movements_type');
            }
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            if ($this->hasIndex('purchase_orders', 'idx_purchase_orders_supplier_status')) {
                $table->dropIndex('idx_purchase_orders_supplier_status');
            }
        });
        Schema::table('sales_returns', function (Blueprint $table) {
            if ($this->hasIndex('sales_returns', 'idx_sales_returns_status_date')) {
                $table->dropIndex('idx_sales_returns_status_date');
            }
        });
    }
};
