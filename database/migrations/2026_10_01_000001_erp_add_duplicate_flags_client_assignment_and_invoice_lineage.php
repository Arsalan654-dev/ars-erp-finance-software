<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Point 9/10: duplicate lead flag + lineage
        Schema::table('leads', function (Blueprint $table): void {
            $table->boolean('is_duplicate')->default(false)->after('campaign_name');
            $table->unsignedBigInteger('duplicate_of_lead_id')->nullable()->after('is_duplicate');

            $table->foreign('duplicate_of_lead_id')->references('id')->on('leads')->onDelete('set null');
        });

        // Point 11: every client is distributed to a CSR
        Schema::table('clients', function (Blueprint $table): void {
            $table->unsignedBigInteger('assigned_to')->nullable()->after('created_by');

            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });

        // Point 14: invoice duplicate lineage (new number always)
        Schema::table('invoices', function (Blueprint $table): void {
            $table->unsignedBigInteger('duplicate_of_id')->nullable()->after('quotation_id');

            $table->foreign('duplicate_of_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropForeign(['duplicate_of_id']);
            $table->dropColumn('duplicate_of_id');
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn('assigned_to');
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropForeign(['duplicate_of_lead_id']);
            $table->dropColumn(['is_duplicate', 'duplicate_of_lead_id']);
        });
    }
};
