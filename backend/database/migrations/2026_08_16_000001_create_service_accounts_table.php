<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('service_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();
            $table->string('username')->unique();
            $table->string('password_hash');
            $table->string('radius_profile')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('last_provisioned_at')->nullable();
            $table->json('provisioning_metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['subscription_id']);
            $table->index(['customer_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('service_accounts'); }
};
