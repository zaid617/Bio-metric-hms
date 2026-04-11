@extends('layouts.app')

@section('title')
    Edit Device
@endsection

@section('content')
    <x-page-title title="Edit Device" subtitle="{{ $device->device_name }}" />

    <div class="row">
        <div class="col-xl-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form id="device-update-form" action="{{ route('attendance.devices.update', $device) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="branch_id" class="form-label">Branch *</label>
                            <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                            {{ old('branch_id', $device->branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="device_name" class="form-label">Device Name *</label>
                            <input type="text" name="device_name" id="device_name"
                                   class="form-control @error('device_name') is-invalid @enderror"
                                   value="{{ old('device_name', $device->device_name) }}" required>
                            @error('device_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="device_serial_number" class="form-label">Serial Number</label>
                            <input type="text" name="device_serial_number" id="device_serial_number"
                                   class="form-control @error('device_serial_number') is-invalid @enderror"
                                   value="{{ old('device_serial_number', $device->device_serial_number) }}">
                            @error('device_serial_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="ip_address" class="form-label">IP Address *</label>
                                <input type="text" name="ip_address" id="ip_address"
                                       class="form-control @error('ip_address') is-invalid @enderror"
                                       value="{{ old('ip_address', $device->ip_address) }}" required>
                                @error('ip_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="port" class="form-label">Port *</label>
                                <input type="number" name="port" id="port"
                                       class="form-control @error('port') is-invalid @enderror"
                                       value="{{ old('port', $device->port) }}" required>
                                @error('port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Device Password</label>
                                <input type="text" name="password" id="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       value="{{ old('password', $device->password) }}">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="com_key" class="form-label">Com Key</label>
                                <input type="text" name="com_key" id="com_key"
                                       class="form-control @error('com_key') is-invalid @enderror"
                                       value="{{ old('com_key', $device->com_key) }}">
                                @error('com_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="sync_interval_minutes" class="form-label">Sync Interval (minutes) *</label>
                            <input type="number" name="sync_interval_minutes" id="sync_interval_minutes"
                                   class="form-control @error('sync_interval_minutes') is-invalid @enderror"
                                   value="{{ old('sync_interval_minutes', $device->sync_interval_minutes) }}"
                                   min="1" max="60" required>
                            @error('sync_interval_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                       value="1" {{ old('is_active', $device->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active (Enable automatic sync)
                                </label>
                            </div>
                        </div>

                    </form>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('attendance.devices.index') }}" class="btn btn-secondary">
                             Cancel
                        </a>
                        <div class="d-flex gap-2">
                            <form action="{{ route('attendance.devices.destroy', $device) }}"
                                  method="POST"
                                  class="m-0"
                                  onsubmit="return confirm('Are you sure you want to delete this device?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    Delete
                                </button>
                            </form>
                            <button type="submit" form="device-update-form" class="btn btn-primary">
                                Update Device
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
