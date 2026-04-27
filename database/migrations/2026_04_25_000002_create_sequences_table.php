<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->unsignedBigInteger('value')->default(0);
            $table->string('prefix', 10)->nullable();
        });

        // Names MUST match exactly what the services pass to SequenceService::next()
        DB::table('sequences')->insert([
            ['name' => 'invoice',          'prefix' => 'INV', 'value' => 0],
            ['name' => 'purchase_order',   'prefix' => 'PO',  'value' => 0],
            ['name' => 'sales_return',     'prefix' => 'RET', 'value' => 0],
            ['name' => 'journal_entry',    'prefix' => 'JE',  'value' => 0],
            ['name' => 'supplier_payment', 'prefix' => 'PAY', 'value' => 0],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
