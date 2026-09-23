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
        Schema::create('clients', function (Blueprint $table) {
            $table->id('recid')->comment('Record ID');
            $table->string('comcode')->unique()->comment('Company Code');
            $table->string('comname')->comment('Company Name');
            $table->string('comadd')->comment('Complete Address');
            $table->string('comcity')->comment('Location');
            $table->string('comnob')->comment('Nature of Business');
            $table->string('bnkname')->comment('Bank Code');
            $table->string('bnkbrn')->comment('Bank Branch');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
