@extends('layouts.app')

@section('title')
    Edit Attendance Record
@endsection

@section('content')
    <x-page-title title="Edit Attendance Record" subtitle="{{ $record->employee->name }} - {{ $record->attendance_date->format('Y-m-d') }}" />

    <div class="row">
        <div class="col-xl-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('attendance.records.update', $record) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <input type="text" class="form-control" value="{{ $record->employee->name }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="text" class="form-control" value="{{ $record->attendance_date->format('Y-m-d') }}" readonly>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="check_in" class="form-label">Check In Time</label>
                                <input type="time" name="check_in" id="check_in"
                                       class="form-control @error('check_in') is-invalid @enderror"
                                       value="{{ old('check_in', $record->check_in ? substr($record->check_in, 0, 5) : '') }}">
                                @error('check_in')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="check_out" class="form-label">Check Out Time</label>
                                <input type="time" name="check_out" id="check_out"
                                       class="form-control @error('check_out') is-invalid @enderror"
                                       value="{{ old('check_out', $record->check_out ? substr($record->check_out, 0, 5) : '') }}">
                                @error('check_out')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status *</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="present" {{ old('status', $record->status) == 'present' ? 'selected' : '' }}>Present</option>
                                <option value="late" {{ old('status', $record->status) == 'late' ? 'selected' : '' }}>Late</option>
                                <option value="absent" {{ old('status', $record->status) == 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="half_day" {{ old('status', $record->status) == 'half_day' ? 'selected' : '' }}>Half Day</option>
                                <option value="leave" {{ old('status', $record->status) == 'leave' ? 'selected' : '' }}>Leave</option>
                                <option value="holiday" {{ old('status', $record->status) == 'holiday' ? 'selected' : '' }}>Holiday</option>
                                <option value="weekend" {{ old('status', $record->status) == 'weekend' ? 'selected' : '' }}>Weekend</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="admin_note" class="form-label">Admin Note</label>
                            <textarea name="admin_note" id="admin_note" rows="3"
                                      class="form-control @error('admin_note') is-invalid @enderror">{{ old('admin_note', $record->admin_note) }}</textarea>
                            @error('admin_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($record->is_manually_adjusted)
                            <div class="alert alert-info">
                                <strong>Previously Adjusted By:</strong> {{ $record->adjustedBy->name ?? 'Unknown' }}<br>
                                <strong>Adjusted At:</strong> {{ $record->adjusted_at ? $record->adjusted_at->format('Y-m-d H:i') : 'N/A' }}
                            </div>
                        @endif

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('attendance.records.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                 Update Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
