<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DoctorAvailabilityController extends Controller
{
    // Show availability for a doctor
    public function index($doctorId)
    {
        try {
            $doctor = Doctor::findOrFail($doctorId);

            $month = request('month', now()->month);
            $year  = request('year', now()->year);

            $datesInMonth = collect(range(1, Carbon::create($year, $month)->daysInMonth))
                ->map(fn($day) => Carbon::create($year, $month, $day)->toDateString());

            $availabilities = DB::table('doctor_availabilities')
                ->where('doctor_id', $doctorId)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get()
                ->keyBy('date');

            return view('doctors.availability.index', compact('doctor', 'datesInMonth', 'availabilities', 'month', 'year'));
        } catch (Exception $e) {
            Log::error('Error loading doctor availability: '.$e->getMessage());
            return redirect()->back()->with('error','Failed to load availability.');
        }
    }

    // Store availability
    public function store(Request $request, $doctorId)
    {
        try {
            $request->validate([
                'morning_start' => 'nullable|array',
                'morning_end'   => 'nullable|array',
                'morning_leave' => 'nullable|array',
                'evening_start' => 'nullable|array',
                'evening_end'   => 'nullable|array',
                'evening_leave' => 'nullable|array',
            ]);

            $allDates = collect(array_keys($request->morning_start ?? []))
                ->merge(array_keys($request->evening_start ?? []))
                ->unique();

            foreach ($allDates as $date) {
                $dayOfWeek = Carbon::parse($date)->format('l');

                $availability = DoctorAvailability::firstOrNew([
                    'doctor_id' => $doctorId,
                    'date'      => $date
                ]);

                // Morning shift
                if(isset($request->morning_leave[$date])) {
                    $availability->morning_start = null;
                    $availability->morning_end   = null;
                    $availability->morning_leave = true;
                } else {
                    $availability->morning_start = $request->morning_start[$date] ?? null;
                    $availability->morning_end   = $request->morning_end[$date] ?? null;
                    $availability->morning_leave = false;
                }

                // Evening shift
                if(isset($request->evening_leave[$date])) {
                    $availability->evening_start = null;
                    $availability->evening_end   = null;
                    $availability->evening_leave = true;
                } else {
                    $availability->evening_start = $request->evening_start[$date] ?? null;
                    $availability->evening_end   = $request->evening_end[$date] ?? null;
                    $availability->evening_leave = false;
                }

                $availability->day_of_week = $dayOfWeek;
                $availability->save();
            }

            return redirect()->back()->with('success','Availability saved successfully.');
        } catch (Exception $e) {
            Log::error('Error saving doctor availability: '.$e->getMessage());
            return redirect()->back()->with('error','Failed to save availability.');
        }
    }

    // Generate next month availability based on current month
    public function generateNextMonth($doctorId)
    {
        try {
            $currentMonthAvail = DoctorAvailability::where('doctor_id', $doctorId)
                ->whereMonth('date', now()->month)
                ->get()
                ->keyBy('day_of_week');

            $nextMonthStart = now()->addMonth()->startOfMonth();
            $nextMonthEnd   = now()->addMonth()->endOfMonth();

            for ($date = $nextMonthStart->copy(); $date->lte($nextMonthEnd); $date->addDay()) {
                $dayName = $date->format('l');
                $source  = $currentMonthAvail[$dayName] ?? null;

                DoctorAvailability::updateOrCreate(
                    [
                        'doctor_id' => $doctorId,
                        'date'      => $date->format('Y-m-d')
                    ],
                    [
                        'morning_start' => $source?->morning_start,
                        'morning_end'   => $source?->morning_end,
                        'morning_leave' => $source?->morning_leave ?? false,
                        'evening_start' => $source?->evening_start,
                        'evening_end'   => $source?->evening_end,
                        'evening_leave' => $source?->evening_leave ?? false,
                        'day_of_week'   => $dayName
                    ]
                );
            }

            return redirect()->route('doctors.availability.index', [
                'doctor' => $doctorId,
                'month'  => now()->addMonth()->month,
                'year'   => now()->addMonth()->year
            ])->with('success','Next month schedule generated successfully.');
        } catch (Exception $e) {
            Log::error('Error generating next month schedule: '.$e->getMessage());
            return redirect()->back()->with('error','Failed to generate next month schedule.');
        }
    }

    // Delete current month
    public function deleteMonth($doctorId)
    {
        try {
            $month = now()->month;
            $year  = now()->year;

            DoctorAvailability::where('doctor_id', $doctorId)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->delete();

            return redirect()->route('doctors.availability.index', ['doctor' => $doctorId])
                ->with('success','Current month schedule deleted successfully.');
        } catch (Exception $e) {
            Log::error('Error deleting month schedule: '.$e->getMessage());
            return redirect()->back()->with('error','Failed to delete schedule.');
        }
    }
}
