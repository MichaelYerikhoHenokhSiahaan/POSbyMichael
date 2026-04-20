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
            $table->decimal('customer_payment', 12, 2)->default(0)->after('total');
            $table->decimal('change_amount', 12, 2)->default(0)->after('customer_payment');
        });

        DB::table('sales')->update([
            'customer_payment' => DB::raw('total'),
            'change_amount' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropColumn(['customer_payment', 'change_amount']);
        });
    }
};
