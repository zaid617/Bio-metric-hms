<?php

namespace App\Http\Controllers\Doctors;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doctor;
// Removed User model import; doctors authenticate via doctor guard directly
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DoctorController extends Controller
{
    // Show all doctors
   public function index()
{
    try {
        $user = auth()->user();

        $query = Doctor::with('branch');

        // Admin → show all doctors
        if ($user->role !== 'admin') {

            // User has branch → show only that branch doctors
            if (!is_null($user->branch_id)) {
                $query->where('branch_id', $user->branch_id);
            }
            // User without branch → show nothing
            else {
                $query->whereRaw('1 = 0');
            }
        }

        $doctors = $query->get();

        return view('doctors.index', compact('doctors'));

    } catch (\Exception $e) {
        \Log::error('Doctor index error: ' . $e->getMessage());
        return back()->with('error', 'Unable to load doctors list.');
    }
}


    // Show doctor detail
    public function show($id)
    {
        try {
            $doctor = Doctor::with('branch')->findOrFail($id);
            return view('doctors.show', compact('doctor'));
        } catch (\Exception $e) {
            \Log::error('Doctor show error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load doctor details.');
        }
    }

    // Show create doctor form
    public function create()
    {
        try {
            $branches = \App\Models\Branch::all();
            return view('doctors.create', compact('branches'));
        } catch (\Exception $e) {
            \Log::error('Doctor create form error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load doctor creation form.');
        }
    }

    // Store new doctor
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'prefix'         => 'required|string|in:Mr.,Ms.,Mrs.',
                'first_name'     => 'required|string|max:255',
                'last_name'      => 'required|string|max:255',
                'email'          => 'required|email|unique:doctors,email',
                'phone'          => 'nullable|string|max:20',
                'specialization' => 'required|string|max:255',
                'password'       => 'required|string|min:8',
                'branch_id'      => 'required|exists:branches,id',
                'cnic'           => 'nullable|string|max:20',
                'dob'            => 'nullable|date',
                'last_education' => 'nullable|string|max:255',
                'document'       => 'nullable|file|mimes:pdf,jpg,png,jpeg',
                'picture'        => 'nullable|image|mimes:jpg,png,jpeg',
                'status'         => 'required|in:active,inactive',
             'shift' => 'required|in:morning,afternoon,evening',


            ]);

           $validated['shift'] = strtolower($validated['shift']);


            // 1️⃣ Handle uploads
            if ($request->hasFile('document')) {
                $validated['document'] = $request->file('document')->store('documents', 'public');
            }
            if ($request->hasFile('picture')) {
                $validated['picture'] = $request->file('picture')->store('pictures', 'public');
            }

            // 2️⃣ Create Doctor (no linked User)
            $doctor = Doctor::create([
                'prefix' => $validated['prefix'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'cnic' => $validated['cnic'],
                'dob' => $validated['dob'],
                'last_education' => $validated['last_education'],
                'specialization' => $validated['specialization'],
                'status' => $validated['status'],
                'shift' => $validated['shift'],

                'branch_id' => $validated['branch_id'],
                'document' => $validated['document'] ?? null,
                'picture' => $validated['picture'] ?? null,
                'password' => Hash::make($validated['password']),
            ]);

            // 3️⃣ Assign doctor role under doctor guard
            // Ensure role exists for doctor guard
            $role = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'doctor']);
            // Assign to Doctor model (doctor guard)
            $doctor->assignRole('doctor');

            return redirect()
                ->route('doctors.index')
                ->with('success', 'Doctor created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            \Log::error('Doctor store error: ' . $e->getMessage());
            return back()->with('error', 'Unable to create doctor. Please try again.')->withInput();
        }
    }

    // Show edit form
    public function edit($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $branches = \App\Models\Branch::all();
            return view('doctors.edit', compact('doctor', 'branches'));
        } catch (\Exception $e) {
            \Log::error('Doctor edit error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load doctor edit form.');
        }
    }

    // Update doctor
    public function update(Request $request, $id)
    {
        try {
            $doctor = Doctor::findOrFail($id);

            $validated = $request->validate([
                'prefix'         => 'required|string|in:Mr.,Ms.,Mrs.',
                'first_name'     => 'required|string|max:255',
                'last_name'      => 'required|string|max:255',
                'email'          => 'required|email|unique:doctors,email,' . $doctor->id,
                'phone'          => 'nullable|string|max:20',
                'specialization' => 'required|string|max:255',
                'branch_id'      => 'required|exists:branches,id',
                'cnic'           => 'nullable|string|max:20',
                'dob'            => 'nullable|date',
                'last_education' => 'nullable|string|max:255',
                'document'       => 'nullable|file|mimes:pdf,jpg,png,jpeg',
                'picture'        => 'nullable|image|mimes:jpg,png,jpeg',
                'status'         => 'required|in:active,inactive',
           'shift' => 'required|in:morning,afternoon,evening',


            ]);
            
            $validated['shift'] = strtolower($validated['shift']);

            // Handle uploads
            if ($request->hasFile('document')) {
                $validated['document'] = $request->file('document')->store('documents', 'public');
            }
            if ($request->hasFile('picture')) {
                $validated['picture'] = $request->file('picture')->store('pictures', 'public');
            }

            // Update doctor (including password if provided)
            if ($request->filled('password')) {
                $validated['password'] = Hash::make($request->password);
            }
            $doctor->update($validated);

            return redirect()
                ->route('doctors.index')
                ->with('success', 'Doctor updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            \Log::error('Doctor update error: ' . $e->getMessage());
            return back()->with('error', 'Unable to update doctor. Please try again.')->withInput();
        }
    }

    // Delete a doctor
    public function destroy($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->delete();

            return redirect()
                ->route('doctors.index')
                ->with('success', 'Doctor deleted successfully!');
        } catch (\Exception $e) {
            \Log::error('Doctor delete error: ' . $e->getMessage());
            return back()->with('error', 'Unable to delete doctor. Please try again.');
        }
    }

    // Show availability page of a doctor
    public function availability($id)
    {
        try {
            $doctor = Doctor::with('availabilities')->findOrFail($id);
            return view('doctors.availability', compact('doctor'));
        } catch (\Exception $e) {
            \Log::error('Doctor availability error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load doctor availability.');
        }
    }
}
