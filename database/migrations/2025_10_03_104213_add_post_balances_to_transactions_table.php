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
            $table->decimal('post_cash_balance', 15, 2)->nullable()->after('type')
                  ->comment('Cash balance after this transaction');
            $table->decimal('post_bank_balance', 15, 2)->nullable()->after('post_cash_balance')
                  ->comment('Bank balance after this transaction');
            $table->decimal('post_branch_balance', 15, 2)->nullable()->after('post_bank_balance')
                  ->comment('Branch balance after this transaction');
            $table->decimal('post_total_balance', 15, 2)->nullable()->after('post_branch_balance')
                  ->comment('Total balance (cash+bank+branch) after this transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'post_cash_balance',
                'post_bank_balance',
                'post_branch_balance',
                'post_total_balance'
            ]);
        });
    }
};
