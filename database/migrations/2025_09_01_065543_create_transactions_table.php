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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); // id (PK)
            $table->unsignedBigInteger('p_id')->nullable();   // patient/user id
            $table->unsignedBigInteger('dr_id')->nullable();  // doctor id
            $table->decimal('amount', 15, 2)->default(0);     // transaction amount
            $table->enum('type', ['+', '-']);                 // + = credit, - = debit
            $table->unsignedBigInteger('b_id')->nullable();   // branch id
            $table->unsignedBigInteger('entery_by')->nullable(); // entered by user id
            $table->string('Remx')->nullable();               // remarks
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
