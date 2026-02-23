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
            // modify existing column to varchar(50)
            $table->string('payment_method', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('transactions', function (Blueprint $table) {
            // rollback (agar pehle int tha)
            $table->integer('payment_method')->nullable()->change();
        });
    }
};
