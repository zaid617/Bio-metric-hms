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
        Schema::table('expenses', function (Blueprint $table) {
            // Check karo agar column pehle se exist nahi karta
            if (!Schema::hasColumn('expenses', 'branch_id')) {
                // branch_id add karo, default 1 for existing records
                $table->unsignedBigInteger('branch_id')->after('id')->default(1);

                // Foreign key setup karo
                $table->foreign('branch_id')
                      ->references('id')
                      ->on('branches')
                      ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Check karo agar column exist karta hai to drop karo
            if (Schema::hasColumn('expenses', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }
};
