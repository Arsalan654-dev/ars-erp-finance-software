<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('provider', ['meta_cloud', 'evolution'])->default('meta_cloud');
            $table->string('phone_number_id');
            $table->string('api_url')->nullable();
            $table->text('api_token');
            $table->string('verify_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};
