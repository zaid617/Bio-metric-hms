@extends('layouts.app')

@section('title', 'Employee Details')

@section('content')
<x-page-title title="Employee" subtitle="Employee Details" />

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h3 class="mb-1">{{ $employee->prefix }} {{ $employee->name }}</h3>
                        <div class="text-muted">{{ $employee->designation }}</div>
                    </div>
                    <a href="{{ url('/employees') }}" class="btn btn-outline-secondary">Back</a>
                </div>

                <div class="row g-3">
                    <div class="col-md-6"><strong>Branch:</strong> {{ $employee->branch->name ?? 'N/A' }}</div>
                    <div class="col-md-6"><strong>Department:</strong> {{ $employee->departmentRecord->name ?? $employee->department ?? 'N/A' }}</div>
                    <div class="col-md-6"><strong>Phone:</strong> {{ $employee->phone }}</div>
                    <div class="col-md-6"><strong>Joining Date:</strong> {{ \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') }}</div>
                    <div class="col-md-6"><strong>Shift:</strong> {{ $employee->shift ?? 'N/A' }}</div>
                    <div class="col-md-6"><strong>Working Hours:</strong> {{ $employee->working_hours ?? 'N/A' }}</div>
                    <div class="col-12">
                        <strong>Appointment Letter:</strong>
                        @if(!empty($employee->appointment_letter))
                            <div class="mt-2">
                                <a href="{{ asset($employee->appointment_letter) }}" target="_blank" class="btn btn-primary">Open Letter</a>
                            </div>
                            @php
                                $letterExtension = strtolower(pathinfo($employee->appointment_letter, PATHINFO_EXTENSION));
                            @endphp
                            @if(in_array($letterExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                <div class="mt-3">
                                    <img src="{{ asset($employee->appointment_letter) }}" alt="Appointment Letter" class="img-fluid rounded border">
                                </div>
                            @endif
                        @else
                            <div class="text-muted">No appointment letter uploaded.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
