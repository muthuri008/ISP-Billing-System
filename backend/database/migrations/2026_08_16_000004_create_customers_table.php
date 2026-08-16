<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number', 40)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 190)->nullable()->index();
            $table->string('phone', 30)->index();
            $table->string('national_id', 80)->nullable()->index();
            $table->enum('status', ['active', 'suspended', 'disconnected'])->default('active')->index();
            $table->enum('billing_type', ['prepaid', 'postpaid'])->default('postpaid');
            $table->date('registered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60)->default('primary');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city', 100);
            $table->string('county', 100)->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->string('country', 100)->default('Kenya');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
