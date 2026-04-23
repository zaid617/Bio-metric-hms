<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkups', function (Blueprint $table) {
            if (!Schema::hasColumn('checkups', 'consultation_type')) {
                $table->string('consultation_type')->nullable()->default(null);
            }

            if (!Schema::hasColumn('checkups', 'pending_amount')) {
                $table->decimal('pending_amount', 15, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        // Intentionally left non-destructive for backward compatibility.
    }
};
