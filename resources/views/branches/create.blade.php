@extends('layouts.app')

@section('title')
    Add Branch
@endsection

@push('css')
    <link href="{{ URL::asset('build/plugins/input-tags/css/tagsinput.css') }}" rel="stylesheet">
@endpush

@section('content')
    <x-page-title title="Add Branch" subtitle="Management" />

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <!-- Display Validation Errors -->
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Add Branch Form -->
                    <form action="{{ route('branches.store') }}" method="POST">
                        @csrf

                        <!-- Branch Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" id="city" name="city" class="form-control" value="{{ old('city') }}" required>
                        </div>

                        <!-- Address -->
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" id="address" name="address" class="form-control" value="{{ old('address') }}">
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>

                        <!-- Prefix -->
                        <div class="mb-3">
                            <label for="prefix" class="form-label">Prefix (For patient MR-)</label>
                            <input type="text" id="prefix" name="prefix" class="form-control" value="{{ old('prefix') }}">
                        </div>

                        <!-- Fee -->
                        <div class="mb-3">
                            <label for="fee" class="form-label">Fee <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" id="fee" name="fee" class="form-control" value="{{ old('fee') }}" required>
                        </div>

                        <!-- Opening Balance -->
                        <div class="mb-3">
                            <label for="opening_balance" class="form-label">Opening Balance (Only in Cash) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" id="opening_balance" name="opening_balance" class="form-control" value="{{ old('opening_balance') }}" required>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="active" {{ old('status')=='active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status')=='inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>



                        <!-- Submit Buttons -->
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="{{ route('branches.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <!-- Plugins -->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
