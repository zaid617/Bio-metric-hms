<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        try {
            $invoices = Invoice::with('patient', 'doctor')->latest()->get();
            return view('invoices.index', compact('invoices'));
        } catch (\Exception $e) {
            \Log::error('Invoice index error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load invoices list.');
        }
    }

    public function create()
    {
        try {
            $patients = Patient::all();
            $doctors  = Doctor::all();
            return view('invoices.create', compact('patients', 'doctors'));
        } catch (\Exception $e) {
            \Log::error('Invoice create form error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load invoice creation form.');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'doctor_id'  => 'required|exists:doctors,id',
                'date'       => 'required|date',
                'fee'        => 'required|numeric',
            ]);

            $invoice = Invoice::create($validated);

            return redirect()
                ->route('invoices.show', $invoice->id)
                ->with('success', 'Invoice created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            \Log::error('Invoice store error: ' . $e->getMessage());
            return back()->with('error', 'Unable to create invoice. Please try again.')
                        ->withInput();
        }
    }

    public function show($id)
    {
        try {
            $invoice = Invoice::with('patient', 'doctor')->findOrFail($id);
            return view('invoices.show', compact('invoice'));
        } catch (\Exception $e) {
            \Log::error('Invoice show error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load invoice details.');
        }
    }
}
