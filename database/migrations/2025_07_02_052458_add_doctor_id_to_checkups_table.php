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
       Schema::table('checkups', function (Blueprint $table) {
        $table->unsignedBigInteger('doctor_id')->after('patient_id'); // ya jis column ke baad chahte hain
        $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkups', function (Blueprint $table) {
        $table->dropForeign(['doctor_id']);
        $table->dropColumn('doctor_id');
    });
    }
};
