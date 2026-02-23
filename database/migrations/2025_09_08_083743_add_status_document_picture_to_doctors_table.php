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
            if (!Schema::hasColumn('doctors', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('specialization');
            }
            if (!Schema::hasColumn('doctors', 'document')) {
                $table->string('document')->nullable()->after('status');
            }
            if (!Schema::hasColumn('doctors', 'picture')) {
                $table->string('picture')->nullable()->after('document');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['status', 'document', 'picture']);
        });
    }
};
