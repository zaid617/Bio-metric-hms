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
       Schema::table('branches', function (Blueprint $table) {
            $table->decimal('fee', 10, 2)->default(0)->after('name'); 
            // 'after' me wo column name den jo fee ke baad aana chahiye
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('fee');
        });
    }
};
