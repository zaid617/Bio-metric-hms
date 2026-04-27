<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('allowance_branch_manager', 10, 2)->nullable()->default(0)->after('allowance_house_rent');
            $table->decimal('allowance_assistant_branch_manager', 10, 2)->nullable()->default(0)->after('allowance_branch_manager');
            $table->json('other_allowances')->nullable()->after('other_allowance_label');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'allowance_branch_manager',
                'allowance_assistant_branch_manager',
                'other_allowances',
            ]);
        });
    }
};
