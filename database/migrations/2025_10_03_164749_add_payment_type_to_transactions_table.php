<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentTypeToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // payment_type field add karna
            $table->tinyInteger('payment_type')
                  ->after('id') // jis column ke baad dikhana ho
                  ->comment('1=Appointment, 2=Session, 3=Expense, 4=Salary, 5=Return');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
}
