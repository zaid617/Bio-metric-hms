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
       Schema::table('transactions', function (Blueprint $table) {

            // 2. Invoice id (nullable because har transaction ke liye zaroori nahi)
            $table->unsignedBigInteger('invoice_id')->nullable()->after('id');

            // 3. Payment method (cash, bank transfer)
            $table->enum('payment_method', ['cash', 'bank_transfer'])->after('invoice_id');

            // 4. Bank id (agar payment method bank_transfer hai to use hoga)
            $table->unsignedBigInteger('bank_id')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'invoice_id', 'payment_method', 'bank_id']);
        });
    }
};
