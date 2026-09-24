<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('clients')->where('bnkname', 'UCPB')->delete();
    }

    public function down(): void
    {
        //
    }
};
