<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- leads: campaign name ----
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('campaign_name', 255)->nullable()->after('source');
        });

        // ---- leads: payment details captured at conversion time ----
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('payment_type', 20)->nullable()->after('converted_at'); // full|partial|special
            $table->decimal('payment_amount', 15, 2)->nullable()->after('payment_type');
            $table->string('payment_method', 50)->nullable()->after('payment_amount');
            $table->string('payment_reference', 100)->nullable()->after('payment_method');
            $table->date('payment_date')->nullable()->after('payment_reference');
            $table->string('payment_screenshot_path', 500)->nullable()->after('payment_date');
        });

        // ---- clients: human-friendly client number (CLI-00001) ----
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('client_number', 20)->nullable()->after('id');
        });

        DB::statement('UPDATE `clients` SET `client_number` = CONCAT(\'CLI-\', LPAD(`id`, 5, \'0\')) WHERE `client_number` IS NULL OR `client_number` = \'\'');

        Schema::table('clients', function (Blueprint $table): void {
            $table->unique('client_number');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropUnique(['client_number']);
            $table->dropColumn('client_number');
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn([
                'campaign_name',
                'payment_type',
                'payment_amount',
                'payment_method',
                'payment_reference',
                'payment_date',
                'payment_screenshot_path',
            ]);
        });
    }
};
