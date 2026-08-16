<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('download_mbps');
            $table->unsignedInteger('upload_mbps');
            $table->decimal('price', 12, 2);
            $table->char('currency', 3)->default('KES');
            $table->enum('billing_cycle', ['daily', 'weekly', 'monthly', 'quarterly', 'annual'])->default('monthly');
            $table->unsignedInteger('data_limit_gb')->nullable();
            $table->unsignedInteger('fair_usage_gb')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
