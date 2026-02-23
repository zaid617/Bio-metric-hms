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
            $table->dropColumn(['diagnosis', 'reason', 'note', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('checkups', function (Blueprint $table) {
            $table->string('diagnosis')->nullable();
            $table->string('reason')->nullable();
            $table->text('note')->nullable();
            $table->date('date')->nullable();
        });
    }
};
