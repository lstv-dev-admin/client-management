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
                'AN TKM BASIC',
                'AN TKM EXPANDED',
                'CS TKM EXP',
                'CS TKM STD',
                'TKM',
                'TKM (EXP)',
                'TKM (STD)'
            ]
        )
            ->update(['prdname' => 'C# TKM']);

        ClientProduct::whereIn(
            'prdname',
            [
                'ACCTG (EXP)',
                'ACCTG (STD)',
                'AN ACCOUNTING BASIC',
                'AN ACCOUNTING EXPANDED',
                'CS ACCTG EXP',
                'CS ACCTG STD',
                'TRACC'
            ]
        )
            ->update(['prdname' => 'C# TRACC']);

        ClientProduct::whereIn(
            'prdname',
            [
                'WEB ACCTG BASIC',
                'WEB ACCTG EXP'
            ]
        )
            ->update(['prdname' => 'WEB TRACC']);

        ClientProduct::whereIn(
            'prdname',
            [
                'WEB ACCTG BASIC',
                'WEB ACCTG EXP'
            ]
        )
            ->update(['prdname' => 'WEB TRACC']);

        ClientProduct::whereIn(
            'prdname',
            [
                'AN ESS'
            ]
        )
            ->update(['prdname' => 'WEB ESS']);

        ClientProduct::whereIn(
            'prdname',
            [
                'AN HRIS BASIC',
                'WEB HRIS BASIC'
            ]
        )
            ->update(['prdname' => 'WEB HRIS (BASIC)']);

        ClientProduct::whereIn(
            'prdname',
            [
                'WEB HRIS EXP',
            ]
        )
            ->update(['prdname' => 'WEB HRIS (FULL)']);

        ClientProduct::whereIn(
            'prdname',
            [
                'WEB PAY BASIC',
                'WEB PAY EXP'
            ]
        )
            ->update(['prdname' => 'WEB PAYROLL']);

        ClientProduct::whereIn(
            'prdname',
            [
                'WEB TKM BASIC',
                'WEB TKM EXP'
            ]
        )
            ->update(['prdname' => 'WEB TKM']);

        ClientProduct::whereIn(
            'prdname',
            [
                'WEB ACCTG CUSTOMIZE'
            ]
        )
            ->update(['prdname' => 'TRACC CUSTOM']);

        ClientProduct::whereIn(
            'prdname',
            [
                'CS OPTIMA',
                'CS OPTIMA ACCTG STD v2',
                'CS OPTIMA PAYROLL STD',
                'CS OPTIMA v2',
                'OPTIMA - BASIC',
                'OPTIMA - EXPANDED'
            ]
        )
            ->update(['prdname' => 'TO REMOVE']);

        ClientProduct::whereIn(
            'prdname',
            [
                'OTHERS',
                'CHECKWRITE (EXP)',
                'Checkwrite (SCB)',
                'CHECKWRITE (STD)',
                'CHECKWRITER',
                'CS CHECKWRITE STD'
            ]
        )
            ->update(['prdname' => 'CHECKWRITE']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
