<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE invoices MODIFY COLUMN payment_method
             ENUM('cash','card','transfer','wallet','credit') NOT NULL"
        );
    }

    public function down(): void
    {
        // Remove rows using 'credit' before reverting the enum, otherwise MySQL rejects it.
        DB::statement("UPDATE invoices SET payment_method = 'cash' WHERE payment_method = 'credit'");

        DB::statement(
            "ALTER TABLE invoices MODIFY COLUMN payment_method
             ENUM('cash','card','transfer','wallet') NOT NULL"
        );
    }
};
