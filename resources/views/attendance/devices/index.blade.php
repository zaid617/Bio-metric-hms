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
                        <div class="d-flex gap-2">
                            <form action="{{ route('attendance.devices.sync-all-now') }}" method="POST" style="display:inline;">
                                @csrf
                                <input type="hidden" name="force_full_sync" value="1">
                                <input type="hidden" name="clear_after_fetch" value="0">
                                <button type="submit" class="btn btn-success"
                                        onclick="return confirm('Sync all active devices with full history? This can take several minutes.')">
                                    Sync All Active Devices
                                </button>
                            </form>
                            <a href="{{ route('attendance.devices.create') }}" class="btn btn-primary">
                                Add New Device
                            </a>
                        </div>
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

                    <div id="sync-alert-container"></div>

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
                                    <tr data-device-row-id="{{ $device->id }}">
                                        <td>{{ $device->id }}</td>
                                        <td>{{ $device->device_name }}</td>
                                        <td>{{ $device->branch->name ?? 'N/A' }}</td>
                                        <td>{{ $device->ip_address }}:{{ $device->port }}</td>
                                        <td>
                                            <span class="status-badge status-{{ $device->connection_status }}">
                                                {{ ucfirst($device->connection_status) }}
                                            </span>
                                        </td>
                                        <td data-last-synced-for="{{ $device->id }}">
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
                                                      method="POST" style="display:inline;"
                                                      class="sync-now-form"
                                                      data-device-id="{{ $device->id }}">
                                                    @csrf
                                                    <input type="hidden" name="force_full_sync" value="1">
                                                    <input type="hidden" name="clear_after_fetch" value="0">
                                                    <button type="submit" class="btn btn-sm btn-success sync-now-btn" title="Sync Now">
                                                        <span class="sync-icon"><i class="material-icons-outlined">sync</i></span>
                                                        <span class="sync-loader d-none">
                                                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                        </span>
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
        function showSyncAlert(type, message) {
            const container = document.getElementById('sync-alert-container');
            if (!container) {
                return;
            }

            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';

            container.innerHTML = '';

            const alertEl = document.createElement('div');
            alertEl.className = `alert ${alertClass} alert-dismissible fade show`;
            alertEl.setAttribute('role', 'alert');
            alertEl.appendChild(document.createTextNode(message || 'Sync completed.'));

            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'btn-close';
            closeButton.setAttribute('data-bs-dismiss', 'alert');

            alertEl.appendChild(closeButton);
            container.appendChild(alertEl);
        }

        function setSyncButtonState(button, isLoading) {
            if (!button) {
                return;
            }

            const icon = button.querySelector('.sync-icon');
            const loader = button.querySelector('.sync-loader');

            button.disabled = isLoading;

            if (icon) {
                icon.classList.toggle('d-none', isLoading);
            }

            if (loader) {
                loader.classList.toggle('d-none', !isLoading);
            }
        }

        function bindSyncNowForms() {
            const forms = document.querySelectorAll('.sync-now-form');

            forms.forEach((form) => {
                form.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    const button = form.querySelector('.sync-now-btn');
                    const deviceId = form.dataset.deviceId;

                    setSyncButtonState(button, true);

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: new FormData(form)
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Sync failed');
                        }

                        if (data.last_synced_at && deviceId) {
                            const lastSyncedCell = document.querySelector(`[data-last-synced-for="${deviceId}"]`);
                            if (lastSyncedCell) {
                                lastSyncedCell.textContent = data.last_synced_at;
                            }
                        }

                        showSyncAlert('success', data.message || 'Sync completed successfully.');
                    } catch (error) {
                        showSyncAlert('error', error.message || 'Sync failed.');
                    } finally {
                        setSyncButtonState(button, false);
                    }
                });
            });
        }

        bindSyncNowForms();

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
