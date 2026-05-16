<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL ALTER TABLE to add transfer_in and transfer_out to the enum
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN movement_type ENUM(
            'add','remove','sale','return','purchase',
            'adjustment','adjustment_add','adjustment_remove',
            'transfer_in','transfer_out'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN movement_type ENUM(
            'add','remove','sale','return','purchase',
            'adjustment','adjustment_add','adjustment_remove'
        ) NOT NULL");
    }
};
