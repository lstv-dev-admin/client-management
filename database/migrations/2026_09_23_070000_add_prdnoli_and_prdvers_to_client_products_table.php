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
        Schema::table('client_products', function (Blueprint $table) {
            $table->string('prdnoli')->nullable()->after('prdname')->comment('Product Number of License');
            $table->string('prdvers')->nullable()->after('prdnoli')->comment('Product Version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_products', function (Blueprint $table) {
            $table->dropColumn(['prdnoli', 'prdvers']);
        });
    }
};
