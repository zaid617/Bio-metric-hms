<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $staticDepartments = [
            'Male Physiotherapy Department',
            'Female Physiotherapy Department',
            'Paeds Physiotherapy Department',
            'Speech Therapy Department',
            'Behavior Therapy Department',
            'Occupational Therapy Department',
            'Remedial Therapy Department',
            'Clinical Psychology Department',
        ];

        $employeeDepartments = DB::table('employees')
            ->whereNotNull('department')
            ->pluck('department')
            ->map(fn ($department) => trim((string) $department))
            ->filter(fn ($department) => $department !== '')
            ->unique()
            ->values()
            ->all();

        $departments = collect($staticDepartments)
            ->merge($employeeDepartments)
            ->map(fn ($department) => trim((string) $department))
            ->filter(fn ($department) => $department !== '')
            ->unique()
            ->values();

        foreach ($departments as $departmentName) {
            Department::firstOrCreate([
                'name' => $departmentName,
            ], [
                'status' => 'active',
            ]);
        }

        $departmentIdsByName = Department::query()
            ->select('id', 'name')
            ->get();

        foreach ($departmentIdsByName as $department) {
            DB::table('employees')
                ->whereNotNull('department')
                ->whereRaw('LOWER(TRIM(department)) = ?', [mb_strtolower($department->name)])
                ->update([
                    'department_id' => $department->id,
                    'department' => $department->name,
                ]);
        }
    }
}
