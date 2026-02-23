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
    Schema::create('expenses', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('expense_type_id');
        $table->decimal('amount', 10, 2);
        $table->string('method'); // Cash, Bank, JazzCash, Easypaisa, etc.
        $table->text('remarks')->nullable();
        $table->unsignedBigInteger('created_by')->nullable();
        $table->timestamps();

        $table->foreign('expense_type_id')->references('id')->on('expense_types')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
