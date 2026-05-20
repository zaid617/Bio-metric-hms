<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_increments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            // Full salary snapshots before and after the change
            $table->json('previous_snapshot');
            $table->json('new_snapshot');

            // Net gross change (sum of new components − sum of previous components)
            $table->decimal('increment_amount', 12, 2);

            // fixed = user entered explicit new values; percentage = all components scaled by %
            $table->enum('increment_type', ['fixed', 'percentage']);

            // Must be today or in the past (enforced at application layer)
            $table->date('effective_from');

            $table->text('reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from'], 'salary_increments_emp_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_increments');
    }
};
