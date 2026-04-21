<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkups', function (Blueprint $table) {
            $table->string('description')->nullable()->after('referred_by_name');
            $table->decimal('discount', 5, 2)->nullable()->default(0)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('checkups', function (Blueprint $table) {
            $table->dropColumn(['description', 'discount']);
        });
    }
};
