<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_annual_price_id')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('annual_price', 10, 2)->nullable();
            $table->integer('apartment_limit')->default(0);
            $table->integer('tenant_limit')->default(0);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
