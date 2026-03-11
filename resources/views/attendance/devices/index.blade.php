@extends('layouts.app')

@section('title')
    Attendance Devices
@endsection

@push('css')
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .status-online {
            background-color: #28a745;
            color: white;
        }
        .status-offline {
            background-color: #dc3545;
            color: white;
        }
        .status-unknown {
            background-color: #6c757d;
            color: white;
        }
    </style>
@endpush

@section('content')
    <x-page-title title="Attendance Devices" subtitle="Manage Biometric Devices" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="mb-0">All Devices</h5>
                        <a href="{{ route('attendance.devices.create') }}" class="btn btn-primary">
                            Add New Device
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Device Name</th>
                                    <th>Branch</th>
                                    <th>IP Address:Port</th>
                                    <th>Status</th>
                                    <th>Last Synced</th>
                                    <th>Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($devices as $device)
                                    <tr>
                                        <td>{{ $device->id }}</td>
                                        <td>{{ $device->device_name }}</td>
                                        <td>{{ $device->branch->name ?? 'N/A' }}</td>
                                        <td>{{ $device->ip_address }}:{{ $device->port }}</td>
                                        <td>
                                            <span class="status-badge status-{{ $device->connection_status }}">
                                                {{ ucfirst($device->connection_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($device->last_synced_at)
                                                {{ $device->last_synced_at->format('Y-m-d H:i') }}
                                            @else
                                                Never
                                            @endif
                                        </td>
                                        <td>
                                            @if($device->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('attendance.devices.edit', $device) }}"
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="material-icons-outlined">edit</i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-info"
                                                        onclick="testConnection({{ $device->id }})" title="Test Connection">
                                                    <i class="material-icons-outlined">wifi</i>
                                                </button>
                                                <form action="{{ route('attendance.devices.sync-now', $device) }}"
                                                      method="POST" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Sync Now">
                                                        <i class="material-icons-outlined">sync</i>
                                                    </button>
                                                </form>
                                                <a href="{{ route('attendance.devices.sync-logs', $device) }}"
                                                   class="btn btn-sm btn-secondary" title="Sync Logs">
                                                    <i class="material-icons-outlined">history</i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No devices found. Add a new device to get started.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $devices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        function testConnection(deviceId) {
            if (confirm('Test connection to this device?')) {
                fetch(`/attendance/devices/${deviceId}/test-connection`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✓ Connection Successful!\n\nDevice Info:\n' + JSON.stringify(data.device_info, null, 2));
                    } else {
                        alert('✗ Connection Failed!\n\n' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error testing connection: ' + error);
                });
            }
        }
    </script>
@endpush
