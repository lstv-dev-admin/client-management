<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('client_products', function (Blueprint $table) {
            $table->id('recid')->comment('Record ID');
            $table->string('comcode')->comment('Company Code');
            $table->foreign('comcode')->references('comcode')->on('clients')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('prdname')->comment('Product Name');
            $table->string('prdnoli')->nullable()->comment('Product Number of License');
            $table->string('prdvers')->nullable()->comment('Product Version');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_products');
    }
};
