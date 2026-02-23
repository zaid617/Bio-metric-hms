@extends('layouts.app')

@section('title')
    User Permissions
@endsection

@push('css')
    <style>
        .user-box {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 6px;
            background: #f9f9f9;
        }
        .permission-label {
            margin-right: 10px;
            margin-top: 5px;
            display: inline-block;
            background: #eee;
            padding: 5px 10px;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
@endpush

@section('content')

<x-page-title title="User Permissions" subtitle="Management" />

<div class="row">
    <div class="col-xl-12">
        @foreach($users as $user)
            <div class="user-box">
                <strong>{{ $user->name ?? $user->email }}</strong>

                <div style="margin-top: 8px;">
                    @foreach($permissions->unique('name') as $permission)
                        <label class="permission-label">
                            <input type="checkbox"
                                   data-user="{{ $user->id }}"
                                   data-permission-id="{{ $permission->id }}"
                                   {{ $user->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                            {{ $permission->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.querySelectorAll('input[type=checkbox]').forEach(cb => {
            cb.addEventListener('change', function() {
                axios.post("{{ route('user.permissions.update') }}", {
                    user_id: this.dataset.user,
                    permission_id: this.dataset.permissionId,
                    has_permission: this.checked ? 1 : 0
                }, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                })
                .then(res => console.log('Permission updated:', res.data))
                .catch(err => console.error('Error updating permission:', err));
            });
        });
    </script>
@endpush
