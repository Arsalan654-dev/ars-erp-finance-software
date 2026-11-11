<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- payments: screenshot amount + mismatch flag (admin fraud flag) ----
        Schema::table('payments', function (Blueprint $table): void {
            $table->decimal('screenshot_amount', 15, 2)->nullable()->after('amount');
            $table->boolean('amount_mismatch')->default(false)->after('screenshot_amount');
        });

        // ---- quotations: exchange rate + duplicate lineage ----
        Schema::table('quotations', function (Blueprint $table): void {
            $table->decimal('exchange_rate', 15, 6)->nullable()->after('currency');
            $table->unsignedBigInteger('duplicate_of_id')->nullable()->after('client_id');

            $table->foreign('duplicate_of_id')->references('id')->on('quotations')->onDelete('set null');
        });

        // ---- leads: special note alongside the lost reason ----
        Schema::table('leads', function (Blueprint $table): void {
            $table->text('lost_note')->nullable()->after('lost_reason');
        });

        // ---- sales package templates (GBP/USD reusable quotation/invoice lines) ----
        Schema::create('sales_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_packages');

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn('lost_note');
        });

        Schema::table('quotations', function (Blueprint $table): void {
            $table->dropForeign(['duplicate_of_id']);
            $table->dropColumn(['exchange_rate', 'duplicate_of_id']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['screenshot_amount', 'amount_mismatch']);
        });
    }
};
