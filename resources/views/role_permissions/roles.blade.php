@extends('layouts.app')

@section('title')
    Role Permissions
@endsection

@push('css')
    {{-- DataTables CSS (optional) --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        /* Common table dropdown fix */
        .table-responsive {
            overflow: visible;
        }

        /* Role Permissions CSS */
        .role-box {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            background: #f9f9f9;
        }
        .permission-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .permission-grid label {
            background: #eee;
            padding: 5px 10px;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
@endpush

@section('content')

<x-page-title title="Role Permissions" subtitle="Management" />

<div class="row">
    <div class="col-xl-12">
        @foreach($roles as $role)
            <div class="role-box">
                <strong>{{ $role->name }}</strong>

                @php
                    $rolePermissionIds = $role->permissions->pluck('id')->toArray();
                @endphp

                <div class="permission-grid">
                    @foreach($permissions as $permission)
                        @if($permission->guard_name == $role->guard_name)
                            <label>
                                <input type="checkbox"
                                       data-role="{{ $role->id }}"
                                       data-permission="{{ $permission->name }}"
                                       {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}>
                                {{ $permission->name }}
                            </label>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

@endsection

@push('script')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Bootstrap JS Bundle (for dropdowns, modals, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS (optional) -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Core Plugins -->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!-- Custom JS -->
    <script>
        document.querySelectorAll('input[type=checkbox]').forEach(cb => {
            cb.addEventListener('change', function() {
                axios.post("{{ route('role.permissions.update') }}", {
                    role_id: this.dataset.role,
                    permission_name: this.dataset.permission,
                    has_permission: this.checked ? 1 : 0
                }, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                })
                .then(response => {
                    console.log('Permission updated');
                    // Optional: show small alert / toast
                })
                .catch(error => {
                    console.error('Error updating permission', error);
                });
            });
        });
    </script>
@endpush
