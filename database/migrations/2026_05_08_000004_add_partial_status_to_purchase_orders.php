<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // إضافة partial للـ status enum
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('pending','partial','received','cancelled') DEFAULT 'pending'");

        Schema::table('purchase_order_items', function (Blueprint $table) {
            // الكمية المطلوبة الأصلية (للمقارنة)
            $table->integer('discrepancy')->default(0)->after('received_quantity')
                ->comment('الفرق بين المطلوب والمستلم — سالب = ناقص، موجب = زيادة');
            $table->text('discrepancy_notes')->nullable()->after('discrepancy');
        });
    }
    public function down(): void {
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('pending','received','cancelled') DEFAULT 'pending'");
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['discrepancy', 'discrepancy_notes']);
        });
    }
};
