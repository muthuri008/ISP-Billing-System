<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('network_sessions', function(Blueprint $table){$table->id();$table->foreignId('service_account_id')->nullable()->constrained()->nullOnDelete();$table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();$table->string('session_id')->unique();$table->dateTime('started_at');$table->dateTime('ended_at')->nullable();$table->unsignedBigInteger('input_octets')->default(0);$table->unsignedBigInteger('output_octets')->default(0);$table->unsignedInteger('duration_seconds')->default(0);$table->ipAddress('nas_address')->nullable();$table->ipAddress('ip_address')->nullable();$table->string('status')->default('online');$table->string('termination_reason')->nullable();$table->json('accounting_metadata')->nullable();$table->timestamps();$table->index(['service_account_id','status']);$table->index(['router_id','started_at']);}); }
 public function down(): void { Schema::dropIfExists('network_sessions'); }
};
