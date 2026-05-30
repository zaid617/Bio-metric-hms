@extends('layouts.app')

@section('title')
    Add New Device
@endsection

@section('content')
    <x-page-title title="Add New Device" subtitle="Attendance Management" />

    <div class="row">
        <div class="col-xl-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('attendance.devices.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Branch *</label>
                            @if(user_can_manage_all_branches(auth()->user()))
                                <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                                <input type="text" class="form-control" value="{{ auth()->user()?->branch?->name ?? 'N/A' }}" readonly>
                            @endif
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="device_name" class="form-label">Device Name *</label>
                            <input type="text" name="device_name" id="device_name"
                                   class="form-control @error('device_name') is-invalid @enderror"
                                   value="{{ old('device_name') }}" required>
                            @error('device_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="device_serial_number" class="form-label">Serial Number</label>
                            <input type="text" name="device_serial_number" id="device_serial_number"
                                   class="form-control @error('device_serial_number') is-invalid @enderror"
                                   value="{{ old('device_serial_number') }}">
                            @error('device_serial_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="ip_address" class="form-label">IP Address *</label>
                                <input type="text" name="ip_address" id="ip_address"
                                       class="form-control @error('ip_address') is-invalid @enderror"
                                       value="{{ old('ip_address') }}" placeholder="192.168.1.100" required>
                                @error('ip_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="port" class="form-label">Port *</label>
                                <input type="number" name="port" id="port"
                                       class="form-control @error('port') is-invalid @enderror"
                                       value="{{ old('port', 4370) }}" required>
                                @error('port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="protocol" class="form-label">Connection Protocol</label>
                            <select name="protocol" id="protocol"
                                    class="form-select @error('protocol') is-invalid @enderror">
                                <option value="" {{ old('protocol') === null || old('protocol') === '' ? 'selected' : '' }}>
                                    Auto-detect (recommended)
                                </option>
                                <option value="tcp" {{ old('protocol') === 'tcp' ? 'selected' : '' }}>
                                    TCP (newer devices, 2024+)
                                </option>
                                <option value="udp" {{ old('protocol') === 'udp' ? 'selected' : '' }}>
                                    UDP (older devices)
                                </option>
                            </select>
                            <small class="text-muted">Auto-detect tries TCP first, then falls back to UDP. Pin to one protocol only if you know which the device uses, to skip the fallback attempt.</small>
                            @error('protocol')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">CommKey / Device Password</label>
                                <input type="number" name="password" id="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       value="{{ old('password', 0) }}" min="0" max="999999" placeholder="0">
                                <small class="text-muted">Found on the device under Menu &rarr; Comm &rarr; Security &rarr; Comm Key. Leave as 0 if the device has no CommKey set. Required for most 2024+ ZKTeco firmware.</small>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="com_key" class="form-label">Com Key</label>
                                <input type="text" name="com_key" id="com_key"
                                       class="form-control @error('com_key') is-invalid @enderror"
                                       value="{{ old('com_key', '0') }}">
                                <small class="text-muted">Default is 0</small>
                                @error('com_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="sync_interval_minutes" class="form-label">Sync Interval (minutes) *</label>
                            <input type="number" name="sync_interval_minutes" id="sync_interval_minutes"
                                   class="form-control @error('sync_interval_minutes') is-invalid @enderror"
                                   value="{{ old('sync_interval_minutes', 5) }}" min="1" max="60" required>
                            @error('sync_interval_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('attendance.devices.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Save Device
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
