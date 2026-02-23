@extends('layouts.app')

@section('title')
    User Permissions
@endsection

@push('css')
    {{-- DataTables CSS (optional, agar table add karna ho future me) --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        /* Allow dropdown to overflow table container */
        .table-responsive {
            overflow: visible;
        }

        /* Permission Grid CSS */
        .permissions-wrapper {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
        }

        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 12px;
        }

        .permission-item {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 12px;
            transition: 0.2s;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .permission-item:hover {
            background: #eef2ff;
            border-color: #c7d2fe;
        }

        .permission-item input {
            margin-right: 8px;
            transform: scale(1.1);
        }
    </style>
@endpush

@section('content')

<x-page-title title="{{ $user->name }} Permissions" subtitle="Manage individual permissions" />

<div class="permissions-wrapper">
    <div class="permissions-grid">
        @foreach($permissions->unique('name') as $permission)
            <label class="permission-item">
                <input type="checkbox"
                       class="permission-checkbox"
                       data-user="{{ $user->id }}"
                       data-permission-id="{{ $permission->id }}"
                       {{ $user->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                {{ ucwords(str_replace('-', ' ', $permission->name)) }}
            </label>
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

    <!-- Pass route dynamically to JS -->
    <script>
        window.USER_PERMISSION_UPDATE_URL = "{{ route('user.permissions.update') }}";
    </script>

    <!-- Custom JS -->
    <script src="{{ asset('js/user-permissions.js') }}"></script>
@endpush
