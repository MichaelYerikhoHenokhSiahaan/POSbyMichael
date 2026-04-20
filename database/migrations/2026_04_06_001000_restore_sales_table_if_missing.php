<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->string('cashier_name')->default('Admin');
                $table->string('payment_method')->default('cash');
                $table->decimal('subtotal', 12, 2);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('total', 12, 2);
                $table->decimal('customer_payment', 12, 2)->default(0);
                $table->decimal('change_amount', 12, 2)->default(0);
                $table->timestamp('sold_at');
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'customer_payment')) {
                $table->decimal('customer_payment', 12, 2)->default(0)->after('total');
            }

            if (! Schema::hasColumn('sales', 'change_amount')) {
                $table->decimal('change_amount', 12, 2)->default(0)->after('customer_payment');
            }
        });
    }

    public function down(): void {}
};
