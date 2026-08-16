<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('radius_nas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nasname', 255)->unique();
            $table->string('shortname', 120)->unique();
            $table->string('type', 40)->default('mikrotik');
            $table->text('secret');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('radius_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_account_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('username', 120)->unique();
            $table->string('auth_type', 30)->default('password');
            $table->text('password')->nullable();
            $table->enum('status', ['active', 'suspended', 'disabled'])->default('active')->index();
            $table->timestamps();
        });

        Schema::create('radius_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name', 120)->unique();
            $table->string('download_speed', 40)->nullable();
            $table->string('upload_speed', 40)->nullable();
            $table->unsignedBigInteger('session_timeout')->nullable();
            $table->unsignedBigInteger('data_limit_bytes')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();
        });

        Schema::create('radius_accounting', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radius_user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();
            $table->string('acct_session_id', 191)->unique();
            $table->string('username', 120)->index();
            $table->string('nas_ip_address', 45)->nullable();
            $table->string('framed_ip_address', 45)->nullable();
            $table->timestamp('acct_start_time')->nullable();
            $table->timestamp('acct_stop_time')->nullable();
            $table->unsignedBigInteger('acct_input_octets')->default(0);
            $table->unsignedBigInteger('acct_output_octets')->default(0);
            $table->string('acct_terminate_cause', 80)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radius_accounting');
        Schema::dropIfExists('radius_profiles');
        Schema::dropIfExists('radius_users');
        Schema::dropIfExists('radius_nas');
    }
};
