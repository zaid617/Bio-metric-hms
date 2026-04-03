<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Architecture note:
     * We use flat columns on employees because this HMS already stores fixed compensation
     * inputs (for example basic_salary and working_hours) on employees and payroll generation
     * reads employee fields directly. This keeps create/update/payroll flows backward compatible
     * while adding the requested allowance and incentive breakdown.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('incentive_sunday_roster', 10, 2)->nullable()->default(0)->after('basic_salary');
            $table->decimal('incentive_home_visit', 10, 2)->nullable()->default(0)->after('incentive_sunday_roster');
            $table->decimal('incentive_speech_therapy', 10, 2)->nullable()->default(0)->after('incentive_home_visit');
            $table->decimal('incentive_dry_needling', 10, 2)->nullable()->default(0)->after('incentive_speech_therapy');

            $table->decimal('allowance_allied_health_council', 10, 2)->nullable()->default(0)->after('incentive_dry_needling');
            $table->decimal('allowance_house_job', 10, 2)->nullable()->default(0)->after('allowance_allied_health_council');
            $table->decimal('allowance_conveyance', 10, 2)->nullable()->default(0)->after('allowance_house_job');
            $table->decimal('allowance_medical', 10, 2)->nullable()->default(0)->after('allowance_conveyance');
            $table->decimal('allowance_house_rent', 10, 2)->nullable()->default(0)->after('allowance_medical');

            $table->decimal('other_allowance', 10, 2)->nullable()->default(0)->after('allowance_house_rent');
            $table->string('other_allowance_label', 255)->nullable()->after('other_allowance');

            $table->index('allowance_house_rent', 'employees_allowance_house_rent_idx');
            $table->index('incentive_sunday_roster', 'employees_incentive_sunday_roster_idx');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_allowance_house_rent_idx');
            $table->dropIndex('employees_incentive_sunday_roster_idx');

            $table->dropColumn([
                'incentive_sunday_roster',
                'incentive_home_visit',
                'incentive_speech_therapy',
                'incentive_dry_needling',
                'allowance_allied_health_council',
                'allowance_house_job',
                'allowance_conveyance',
                'allowance_medical',
                'allowance_house_rent',
                'other_allowance',
                'other_allowance_label',
            ]);
        });
    }
};
