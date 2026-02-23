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
         Schema::table('doctors', function (Blueprint $table) {
            $table->string('first_name')->after('branch_id');
            $table->string('last_name')->after('first_name');
            $table->string('cnic')->nullable()->after('phone');
            $table->date('dob')->nullable()->after('cnic');
            $table->string('last_education')->nullable()->after('dob');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'cnic', 'dob', 'last_education']);
        });
    }
};
