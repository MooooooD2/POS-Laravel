<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track returned quantity per invoice line to avoid re-querying ReturnItems on every return
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->unsignedInteger('returned_qty')->default(0)->after('quantity');
            $table->decimal('returned_tax', 10, 2)->default(0)->after('tax_amount');
        });

        // Partial-receive quality tracking on purchase order items
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->unsignedInteger('rejected_qty')->default(0)->after('received_quantity');
            $table->enum('quality_status', ['pending', 'passed', 'rejected'])->default('pending')->after('rejected_qty');
        });

        // Cash drawer movements (deposits / withdrawals / adjustments during a session)
        Schema::create('cash_session_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_session_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['deposit', 'withdrawal', 'adjustment']);
            $table->decimal('amount', 15, 2);
            $table->string('reason')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();

            $table->index(['cash_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_session_movements');

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['rejected_qty', 'quality_status']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['returned_qty', 'returned_tax']);
        });
    }
};
