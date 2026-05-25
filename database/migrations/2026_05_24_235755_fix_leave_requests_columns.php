<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix leave_requests column names so they match the API route code:
 *   start_date    → starts_at
 *   end_date      → ends_at
 *   leave_type_id (FK) → leave_type (string: annual|sick|unpaid)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add simple string column first (before touching the FK)
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('leave_type', 20)->default('annual')->after('user_id');
        });

        // 2. Rename date columns — raw SQL is safest on MariaDB 10.4
        DB::statement("ALTER TABLE leave_requests CHANGE `start_date` `starts_at` DATE NOT NULL");
        DB::statement("ALTER TABLE leave_requests CHANGE `end_date`   `ends_at`   DATE NOT NULL");

        // 3. Drop FK, then drop the obsolete leave_type_id column
        Schema::table('leave_requests', function (Blueprint $table) {
            try {
                $table->dropForeign(['leave_type_id']);
            } catch (\Throwable) {
                // already absent or named differently — safe to ignore
            }
            $table->dropColumn('leave_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('leave_type');
            $table->unsignedBigInteger('leave_type_id')->default(1)->after('user_id');
        });

        DB::statement("ALTER TABLE leave_requests CHANGE `starts_at` `start_date` DATE NOT NULL");
        DB::statement("ALTER TABLE leave_requests CHANGE `ends_at`   `end_date`   DATE NOT NULL");
    }
};
