<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;


class PatientController extends Controller
{
    /**
     * Display a listing of patients.
     */
    public function index(Request $request)
    {
        try {
            $query = Patient::with('branch');

            if (auth()->user()->role !== 'admin') {
                $query->where('branch_id', auth()->user()->branch_id);
            }

            // Filter by patient ID if provided
            if ($request->filled('search_id')) {
                $query->where('id', $request->search_id);
            }

            $patients = $query->get();

            return view('patients.indexx', compact('patients'));
        } catch (\Exception $e) {
            Log::error('Patient index error: ' . $e->getMessage());
            return back()->with('error', 'Unable to fetch patients. Please try again.');
        }
    }

    /**
     * Show the form for creating a new patient.
     */
 public function create()
{
    try {
        $branches = Branch::select('id', 'name')->get();

        $doctors = DB::table('doctors')
            ->select(
                'id',
                DB::raw("CONCAT(first_name,' ',last_name) as name")
            )
            ->get();

        $patients = Patient::select('id', 'name')->get();

        return view('patients.create', compact(
            'branches',
            'doctors',
            'patients'
        ));

    } catch (\Exception $e) {
        dd($e->getMessage());
    }
}

/**
     * Store a newly created patient in storage.
     */
   public function store(Request $request)
{
    try {
        $validatedData = $request->validate([
            'prefix'        => 'required|string|in:Mr.,Ms.,Mrs.', 
            'name'          => 'required|string|max:255',
            'gender'        => 'required|in:Male,Female,Other',
            'guardian_name' => 'required|string|max:255',
            'age'           => 'required|numeric',
            'phone'         => 'required|string|max:20',
            'cnic'          => 'nullable|string|max:15|unique:patients,cnic',
            'address'       => 'required|string|max:500', // ✅ FIXED
            'branch_id'     => 'required|exists:branches,id',
            'type_select'   => 'nullable|string',
            'sub_select'    => 'nullable|string',
        ]);

        Patient::create($validatedData);

        return redirect()->route('patients.index')
            ->with('success', 'Patient added successfully!');

    } catch (\Illuminate\Validation\ValidationException $e) {
        return back()->withErrors($e->validator)->withInput();
    } catch (\Exception $e) {
        \Log::error('Patient store error: ' . $e->getMessage());
        return back()->with('error', $e->getMessage())->withInput();
    }
}

    /**
     * Show the form for editing a patient.
     */
 /**
 * Show the form for editing a patient.
 */
public function edit($id)
{
    try {
        $patient  = Patient::findOrFail($id);
        $branches = Branch::all();

        // ✅ Doctors as string array (for JS)
        $doctors = DB::table('doctors')
            ->select(DB::raw("CONCAT(first_name,' ',last_name) as name"))
            ->pluck('name') // sirf names ka array
            ->toArray();

        // ✅ Patients as string array (for JS)
        $patients = Patient::pluck('name')->toArray();

        return view('patients.edit', compact('patient', 'branches', 'doctors', 'patients'));
    } catch (\Exception $e) {
        \Log::error('Patient edit error: ' . $e->getMessage());
        return back()->with('error', 'Unable to load patient edit form.');
    }
}


    /**
     * Update the specified patient in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'prefix'        => 'required|string|in:Mr.,Ms.,Mrs.',
                'name'          => 'required|string|max:255',
                'gender'        => 'required|in:Male,Female,Other',
                'guardian_name' => 'required|string|max:255',
                'age'           => 'required|numeric',
                'phone'         => 'required|string|max:20',
                'cnic' => 'nullable|string|regex:/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/|unique:patients,cnic,'.$id,

                'address'       => 'required|string|max:500',
                'branch_id'     => 'required|exists:branches,id',
                'type_select'   => 'nullable|string',
                'sub_select'    => 'nullable|string',
            ]);

            $patient = Patient::findOrFail($id);
            $patient->update($request->only(
                'prefix',
                'name',
                'gender',
                'guardian_name',
                'age',
                'phone',
                'cnic',
                'address',
                'branch_id',
                'type_select',  
                'sub_select'
            ));

            return redirect('/patients')->with('success', 'Patient updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            \Log::error('Patient update error: ' . $e->getMessage());
            return back()->with('error', 'Unable to update patient. Please try again.')->withInput();
        }
    }

    /**
     * Display the specified patient with branch and checkups.
     */
    public function show($id)
    {
        try {
            $patient = Patient::with('branch', 'checkups')->findOrFail($id);
            return view('patients.show', compact('patient'));
        } catch (\Exception $e) {
            \Log::error('Patient show error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load patient details.');
        }
    }

    /**
     * Remove the specified patient from storage.
     */
    public function destroy($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            $patient->delete();

            return redirect('/patients')->with('success', 'Patient deleted successfully!');
        } catch (\Exception $e) {
            \Log::error('Patient delete error: ' . $e->getMessage());
            return back()->with('error', 'Unable to delete patient. Please try again.');
        }
    }
}
