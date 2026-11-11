<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- payments: acknowledge step (pending -> acknowledged -> verified) ----
        Schema::table('payments', function (Blueprint $table): void {
            $table->unsignedBigInteger('lead_id')->nullable()->after('client_id');
            $table->string('screenshot_path', 500)->nullable()->after('reference_number');
            $table->unsignedBigInteger('acknowledged_by')->nullable()->after('verified_by');
            $table->timestamp('acknowledged_at')->nullable()->after('acknowledged_by');

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- leads: payment currency + special approval audit ----
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('payment_currency', 10)->nullable()->default('USD')->after('payment_amount');
            $table->string('payment_approval_note', 500)->nullable()->after('payment_screenshot_path');
            $table->unsignedBigInteger('payment_approved_by')->nullable()->after('payment_approval_note');
            $table->foreign('payment_approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropForeign(['payment_approved_by']);
            $table->dropColumn([
                'payment_currency',
                'payment_approval_note',
                'payment_approved_by',
            ]);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['acknowledged_by']);
            $table->dropForeign(['lead_id']);
            $table->dropColumn([
                'lead_id',
                'screenshot_path',
                'acknowledged_by',
                'acknowledged_at',
            ]);
        });
    }
};
