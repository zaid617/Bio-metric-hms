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
       Schema::table('treatment_sessions', function (Blueprint $table) {
            $table->decimal('paid_amount', 8, 2)->nullable()->after('session_fee');
            $table->decimal('dues_amount', 8, 2)->nullable()->after('paid_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('treatment_sessions', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'dues_amount']);
        });
    }
};
