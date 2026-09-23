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
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id('recid')->comment('Record ID');
            $table->string('comcode')->comment('Company Code');
            $table->foreign('comcode')->references('comcode')->on('clients')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('conperson')->comment('Contact Person');
            $table->string('condesig')->comment('Designation');
            $table->string('contactnum')->comment('Contact No.');
            $table->string('conemail')->comment('Email');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_contacts');
    }
};
