@extends('layouts.app')

@section('title')
    Device Sync Logs
@endsection

@push('css')
    <style>
        .status-success { background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; }
        .status-failed { background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; }
        .status-partial { background-color: #ffc107; color: black; padding: 4px 8px; border-radius: 4px; }
        .log-details { font-size: 0.85rem; color: #6c757d; }
    </style>
@endpush

@section('content')
    <x-page-title title="Device Sync Logs" subtitle="Synchronization History" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <!-- Filter Form -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Device</label>
                            <select name="device_id" class="form-select">
                                <option value="">All Devices</option>
                                @foreach($devices as $deviceOption)
                                    <option value="{{ $deviceOption->id }}" {{ request('device_id') == $deviceOption->id ? 'selected' : '' }}>
                                        {{ $deviceOption->device_name }} ({{ $deviceOption->ip_address }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Sync Type</label>
                            <select name="sync_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="users" {{ request('sync_type') == 'users' ? 'selected' : '' }}>Users</option>
                                <option value="attendance" {{ request('sync_type') == 'attendance' ? 'selected' : '' }}>Attendance</option>
                                <option value="full" {{ request('sync_type') == 'full' ? 'selected' : '' }}>Full Sync</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                         Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sync Logs -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Synchronization Logs</h6>
                    <a href="{{ route('attendance.devices.index') }}" class="btn btn-sm btn-secondary">
                        <i class="material-icons-outlined">arrow_back</i> Back to Devices
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Device</th>
                                    <th>Sync Type</th>
                                    <th>Status</th>
                                    <th>Started At</th>
                                    <th>Duration</th>
                                    <th>Records Synced</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>{{ $log->id }}</td>
                                        <td>
                                            <strong>{{ $log->device->device_name ?? 'N/A' }}</strong><br>
                                            <small class="text-muted">{{ $log->device->ip_address ?? '' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst($log->sync_type ?? 'N/A') }}</span>
                                        </td>
                                        <td>
                                            <span class="status-{{ $log->status }}">
                                                {{ ucfirst($log->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $log->started_at ? $log->started_at->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td>{{ $log->duration_seconds ? number_format($log->duration_seconds, 2) . 's' : 'N/A' }}</td>
                                        <td class="text-center">
                                            @if($log->records_synced > 0)
                                                <span class="badge bg-success">{{ $log->records_synced }}</span>
                                            @else
                                                <span class="badge bg-secondary">0</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->error_message)
                                                <button class="btn btn-sm btn-danger"
                                                        onclick="showLogDetails('{{ addslashes($log->error_message) }}')">
                                                    <i class="material-icons-outlined">error</i> Error
                                                </button>
                                            @elseif($log->details)
                                                <button class="btn btn-sm btn-info"
                                                        onclick="showLogDetails('{{ addslashes(json_encode($log->details)) }}')">
                                                    <i class="material-icons-outlined">info</i> Details
                                                </button>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No sync logs found. Sync a device to see logs.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $logs->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            @if(isset($statistics))
            <div class="card mt-3">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">Sync Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h4 class="text-primary">{{ $statistics['total_syncs'] }}</h4>
                            <p class="mb-0 text-muted">Total Syncs</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-success">{{ $statistics['successful_syncs'] }}</h4>
                            <p class="mb-0 text-muted">Successful</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-danger">{{ $statistics['failed_syncs'] }}</h4>
                            <p class="mb-0 text-muted">Failed</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-info">{{ number_format($statistics['success_rate'], 1) }}%</h4>
                            <p class="mb-0 text-muted">Success Rate</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Log Details Modal -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Log Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="logDetailsContent" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        function showLogDetails(details) {
            try {
                // Try to parse as JSON for pretty printing
                const parsed = JSON.parse(details);
                document.getElementById('logDetailsContent').textContent = JSON.stringify(parsed, null, 2);
            } catch (e) {
                // If not JSON, just display as is
                document.getElementById('logDetailsContent').textContent = details;
            }

            const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
            modal.show();
        }
    </script>
@endpush
