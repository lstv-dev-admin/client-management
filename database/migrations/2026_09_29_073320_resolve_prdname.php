<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\ClientProduct;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ClientProduct::whereIn(
            'prdname',
            [
                'CS EASYPAY EXP',
                'CHINAPAY',
                'AN PAYROLL BASIC',
                'AN PAYROLL EXPANDED',
                'CS EASYPAY STD',
                'CS PAYROLL EXP',
                'CS PAYROLL STD',
                'EASYPAY',
                'EASYPAY CS',
                'PAYROLL',
                'PAYROLL (EXP)',
                'PAYROLL (STD)'

            ]
        )
            ->update(['prdname' => 'C# PAYROLL']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
