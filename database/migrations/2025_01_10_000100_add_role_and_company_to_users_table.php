<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('buyer')->after('password')->index();
            $table->string('company_name')->nullable()->after('role');
            $table->string('phone', 30)->nullable()->after('company_name');
            $table->string('country', 2)->nullable()->after('phone');
            $table->boolean('is_approved')->default(false)->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'company_name', 'phone', 'country', 'is_approved']);
        });
    }
};
