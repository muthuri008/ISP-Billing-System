<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 60)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->enum('method', ['mpesa', 'bank', 'cash', 'card', 'manual']);
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('KES');
            $table->string('external_reference', 120)->nullable()->index();
            $table->string('transaction_reference', 120)->nullable()->unique();
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->timestamps();
            $table->unique(['payment_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};
