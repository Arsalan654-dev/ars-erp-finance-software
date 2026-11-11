<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multi-line sales package templates: each package carries its own
     * priced line items so a whole bundle (e.g. USA Texas LLC Package)
     * can be dropped into a quotation or invoice in one click.
     */
    public function up(): void
    {
        Schema::create('sales_package_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sales_package_id');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('sales_package_id')->references('id')->on('sales_packages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_package_items');
    }
};
