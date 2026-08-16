<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('hostname', 255);
            $table->unsignedSmallInteger('api_port')->default(8728);
            $table->string('radius_secret')->nullable();
            $table->string('api_username')->nullable();
            $table->text('api_password')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active')->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('service_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();
            $table->string('username', 120)->unique();
            $table->text('password_hash')->nullable();
            $table->enum('access_type', ['pppoe', 'hotspot', 'static'])->default('pppoe');
            $table->enum('status', ['active', 'suspended', 'disabled'])->default('active')->index();
            $table->string('mac_address', 32)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });

        Schema::create('network_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 191)->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('input_octets')->default(0);
            $table->unsignedBigInteger('output_octets')->default(0);
            $table->string('nas_address', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_sessions');
        Schema::dropIfExists('service_accounts');
        Schema::dropIfExists('routers');
    }
};
