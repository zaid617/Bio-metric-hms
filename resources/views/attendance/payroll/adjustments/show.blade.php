@extends('layouts.app')

@section('title') Adjustment #{{ $adjustment->id }} @endsection

@section('content')
<x-page-title title="Adjustment #{{ $adjustment->id }}"
    subtitle="{{ $adjustment->employee->name ?? '' }} — {{ \Carbon\Carbon::create($adjustment->year,$adjustment->month)->format('F Y') }}" />

<div class="row">
    <div class="col-xl-7 mx-auto">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center
                @if($adjustment->adjustment_type=='earning') bg-success text-white
                @elseif($adjustment->adjustment_type=='award') bg-warning
                @else bg-danger text-white @endif">
                <div>
                    <span class="material-icons-outlined me-2" style="vertical-align:middle">
                        @if($adjustment->adjustment_type=='earning')add_circle_outline
                        @elseif($adjustment->adjustment_type=='award')emoji_events
                        @else remove_circle_outline@endif
                    </span>
                    {{ ucfirst($adjustment->adjustment_type) }} Adjustment
                </div>
                <span class="badge bg-white
                    @if($adjustment->adjustment_type=='earning') text-success
                    @elseif($adjustment->adjustment_type=='award') text-warning-emphasis
                    @else text-danger @endif fs-6">
                    {{ $adjustment->adjustment_type=='deduction'?'−':'+' }} PKR {{ number_format($adjustment->amount,0) }}
                </span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Employee</dt>
                    <dd class="col-sm-8">{{ $adjustment->employee->name ?? '—' }}</dd>

                    <dt class="col-sm-4">Period</dt>
                    <dd class="col-sm-8">{{ \Carbon\Carbon::create($adjustment->year,$adjustment->month)->format('F Y') }}</dd>

                    <dt class="col-sm-4">Type</dt>
                    <dd class="col-sm-8">{{ ucfirst($adjustment->adjustment_type) }}</dd>

                    <dt class="col-sm-4">Code</dt>
                    <dd class="col-sm-8"><code>{{ $adjustment->code }}</code></dd>

                    <dt class="col-sm-4">Title</dt>
                    <dd class="col-sm-8">{{ $adjustment->title ?: '—' }}</dd>

                    <dt class="col-sm-4">Amount</dt>
                    <dd class="col-sm-8 fw-bold fs-5">
                        <span class="{{ $adjustment->adjustment_type=='deduction'?'text-danger':'text-success' }}">
                            {{ $adjustment->adjustment_type=='deduction'?'−':'+' }} PKR {{ number_format($adjustment->amount,2) }}
                        </span>
                    </dd>

                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        @if($adjustment->payroll_id)
                            <span class="badge bg-secondary">Applied to Payroll #{{ $adjustment->payroll_id }}</span>
                        @else
                            <span class="badge bg-info text-dark">Standalone — Pending Payroll</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Notes / Reason</dt>
                    <dd class="col-sm-8">{{ $adjustment->notes ?? $adjustment->reason ?? '—' }}</dd>

                    <dt class="col-sm-4">Created by</dt>
                    <dd class="col-sm-8">{{ $adjustment->creator->name ?? 'System' }}
                        @if($adjustment->created_at)
                        <span class="text-muted small">— {{ $adjustment->created_at->format('d M Y H:i') }}</span>
                        @endif
                    </dd>
                </dl>
            </div>
            <div class="card-footer d-flex gap-2 justify-content-end">
                <a href="{{ route('attendance.payroll.adjustments.index') }}" class="btn btn-outline-secondary">
                    <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:16px">arrow_back</span> Back
                </a>
                @if(!$adjustment->payroll_id)
                <a href="{{ route('attendance.payroll.adjustments.edit',$adjustment->id) }}" class="btn btn-warning">
                    <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:16px">edit</span> Edit
                </a>
                <form action="{{ route('attendance.payroll.adjustments.destroy',$adjustment->id) }}" method="POST"
                      onsubmit="return confirm('Delete this adjustment permanently?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:16px">delete</span> Delete
                    </button>
                </form>
                @else
                <a href="{{ route('attendance.payroll.show',$adjustment->payroll_id) }}" class="btn btn-outline-primary">
                    View Payroll #{{ $adjustment->payroll_id }}
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
