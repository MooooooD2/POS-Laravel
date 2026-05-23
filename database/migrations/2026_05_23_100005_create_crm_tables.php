<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();  // staff who logged it
            $table->enum('type', [
                'call', 'email', 'visit', 'whatsapp', 'note',
                'complaint', 'follow_up', 'sale', 'return'
            ])->default('note');
            $table->string('subject', 200)->nullable();
            $table->text('notes')->nullable();
            $table->enum('outcome', ['positive', 'neutral', 'negative', 'pending'])->default('neutral');
            $table->timestamp('scheduled_at')->nullable(); // for follow-ups
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });

        // Customer segments (more advanced than groups)
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->json('rules')->nullable(); // JSON filter rules
            $table->integer('customer_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add CRM fields to customers
        if (!Schema::hasColumn('customers', 'segment_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedBigInteger('segment_id')->nullable()->after('group_id');
                $table->enum('lifecycle_stage', ['lead', 'prospect', 'customer', 'loyal', 'at_risk', 'churned'])->default('customer')->after('segment_id');
                $table->timestamp('last_purchase_at')->nullable()->after('lifecycle_stage');
                $table->integer('purchase_count')->default(0)->after('last_purchase_at');
                $table->decimal('lifetime_value', 15, 2)->default(0)->after('purchase_count');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_segments');
        Schema::dropIfExists('crm_activities');
    }
};
