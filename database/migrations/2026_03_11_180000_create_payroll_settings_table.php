<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();

            // Shift & schedule
            $table->decimal('default_shift_hours', 5, 2)->default(8);
            $table->decimal('overtime_multiplier', 5, 2)->default(1.5);
            $table->boolean('work_on_saturday')->default(true);
            $table->string('shift_start', 5)->default('09:00');
            $table->unsignedSmallInteger('late_grace_minutes')->default(15);

            // Commission rates
            $table->decimal('treatment_extension_commission', 5, 4)->default(0.10);
            $table->decimal('assessment_incentive', 5, 4)->default(0.05);
            $table->decimal('personal_patient_commission', 5, 4)->default(0.20);

            // Bonuses
            $table->decimal('satisfactory_session_amount', 10, 2)->default(300);
            $table->unsignedSmallInteger('satisfaction_threshold')->default(90);
            $table->decimal('satisfaction_bonus_per_feedback', 10, 2)->default(200);
            $table->decimal('reference_bonus_per_patient', 10, 2)->default(500);

            // Awards
            $table->decimal('punctuality_amount', 10, 2)->default(2000);

            // Deductions
            $table->decimal('absent_per_day', 10, 2)->default(500);
            $table->decimal('late_per_day', 10, 2)->default(200);

            $table->timestamps();
        });

        // Seed the single settings row with default values
        DB::table('payroll_settings')->insert([
            'default_shift_hours'              => 8,
            'overtime_multiplier'              => 1.5,
            'work_on_saturday'                 => true,
            'shift_start'                      => '09:00',
            'late_grace_minutes'               => 15,
            'treatment_extension_commission'   => 0.10,
            'assessment_incentive'             => 0.05,
            'personal_patient_commission'      => 0.20,
            'satisfactory_session_amount'      => 300,
            'satisfaction_threshold'           => 90,
            'satisfaction_bonus_per_feedback'  => 200,
            'reference_bonus_per_patient'      => 500,
            'punctuality_amount'               => 2000,
            'absent_per_day'                   => 500,
            'late_per_day'                     => 200,
            'created_at'                       => now(),
            'updated_at'                       => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
