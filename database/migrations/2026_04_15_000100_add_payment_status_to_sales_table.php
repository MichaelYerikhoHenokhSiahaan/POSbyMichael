<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->string('payment_status', 20)->nullable()->after('change_amount');
        });

        DB::table('sales')->update([
            'payment_status' => DB::raw("CASE WHEN customer_payment >= total THEN 'Lunas' ELSE 'Belum Lunas' END"),
        ]);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropColumn('payment_status');
        });
    }
};
