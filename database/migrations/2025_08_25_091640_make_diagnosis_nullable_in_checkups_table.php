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
        $table->string('diagnosis')->nullable()->change();
        $table->text('note')->nullable()->change();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('checkups', function (Blueprint $table) {
        $table->string('diagnosis')->nullable(false)->change();
        $table->text('note')->nullable(false)->change();
    });
    }
};
