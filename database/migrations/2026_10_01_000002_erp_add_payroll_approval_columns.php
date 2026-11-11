<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_records', function (Blueprint $table): void {
            $table->unsignedBigInteger('approved_by')->nullable()->after('created_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_records', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at']);
        });
    }
};
