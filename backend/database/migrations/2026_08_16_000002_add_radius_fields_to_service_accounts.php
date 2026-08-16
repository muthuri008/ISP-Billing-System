<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('service_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('service_accounts', 'radius_profile')) $table->string('radius_profile')->nullable();
            if (!Schema::hasColumn('service_accounts', 'last_provisioned_at')) $table->timestamp('last_provisioned_at')->nullable();
            if (!Schema::hasColumn('service_accounts', 'provisioning_metadata')) $table->json('provisioning_metadata')->nullable();
        });
    }
    public function down(): void {
        Schema::table('service_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('service_accounts', 'radius_profile')) $table->dropColumn('radius_profile');
            if (Schema::hasColumn('service_accounts', 'last_provisioned_at')) $table->dropColumn('last_provisioned_at');
            if (Schema::hasColumn('service_accounts', 'provisioning_metadata')) $table->dropColumn('provisioning_metadata');
        });
    }
};
