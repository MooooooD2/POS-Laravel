<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('session_token', 128)->unique();
            $table->string('device_name', 150)->nullable();   // e.g. "Chrome on Windows"
            $table->string('device_type', 50)->nullable();    // desktop, mobile, tablet
            $table->string('browser', 80)->nullable();
            $table->string('os', 80)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('location', 150)->nullable();      // optional geo
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_sessions');
    }
};
