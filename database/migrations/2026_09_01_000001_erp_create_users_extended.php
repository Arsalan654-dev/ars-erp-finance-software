<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id')->nullable()->unique()->after('id');
            $table->string('phone')->nullable();
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->string('active_session_id')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->string('timezone')->nullable();
            $table->string('locale', 10)->default('en');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id', 'phone', 'department', 'designation', 'is_active',
                'must_change_password', 'active_session_id', 'last_activity_at', 'timezone', 'locale',
            ]);
        });
    }
};