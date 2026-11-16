<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->string('rest_scheme', 5)->default('https')->after('hostname');
            $table->unsignedSmallInteger('rest_port')->default(443)->after('rest_scheme');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['rest_scheme', 'rest_port']);
        });
    }
};
