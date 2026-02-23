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
            $table->decimal('paid_amount', 15, 2)->default(0)->after('fee');
            $table->string('payment_method')->nullable()->after('paid_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkups', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'payment_method']);
        });
    }
};
