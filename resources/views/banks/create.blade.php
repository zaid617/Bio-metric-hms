@extends('layouts.app')

@section('title')
    Add Bank
@endsection

@push('css')
    <link href="{{ URL::asset('build/plugins/input-tags/css/tagsinput.css') }}" rel="stylesheet">
@endpush

@section('content')
    <x-page-title title="Add New Bank" subtitle="Management" />

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">

                    <!-- Form -->
                    <form action="{{ route('banks.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Account No</label>
                            <input type="text" name="account_no" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Account Title</label>
                            <input type="text" name="account_title" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Opening Balance</label>
                            <input type="number" step="0.01" name="balance" class="form-control" required>
                        </div>

                        <!-- Save + Back Buttons together -->
                        <div class="d-flex justify-content-start gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">💾 Save Bank</button>
                            <a href="{{ route('banks.index') }}" class="btn btn-secondary">⬅ Back</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush


