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
            $table->string('referred_by_type')->nullable();
            $table->unsignedBigInteger('referred_by_id')->nullable();
            $table->string('referred_by_name')->nullable();

            $table->index('referred_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkups', function (Blueprint $table) {
            $table->dropIndex(['referred_by_id']);
            $table->dropColumn(['referred_by_type', 'referred_by_id', 'referred_by_name']);
        });
    }
};
