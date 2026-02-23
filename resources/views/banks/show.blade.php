@extends('layouts.app')

@section('title')
    Bank Details
@endsection

@push('css')
    <link href="{{ URL::asset('build/plugins/input-tags/css/tagsinput.css') }}" rel="stylesheet">
@endpush

@section('content')
    <x-page-title title="Bank Details" subtitle="Management" />

    <div class="row">
        <!-- full width so content left se start ho -->
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">

                    <!-- Bank Details Table -->
                    <table class="table table-bordered">
                        <tr>
                            <th>ID</th>
                            <td>{{ $bank->id }}</td>
                        </tr>
                        <tr>
                            <th>Bank Name</th>
                            <td>{{ $bank->bank_name }}</td>
                        </tr>
                        <tr>
                            <th>Account No</th>
                            <td>{{ $bank->account_no }}</td>
                        </tr>
                        <tr>
                            <th>Account Title</th>
                            <td>{{ $bank->account_title }}</td>
                        </tr>
                        <tr>
                            <th>Balance</th>
                            <td>{{ number_format($bank->balance, 2) }}</td>
                        </tr>
                    </table>

                    <!-- Back Button at Bottom -->
                    <div class="mt-3">
                        <a href="{{ route('banks.index') }}" class="btn btn-secondary btn-sm">⬅ Back</a>
                    </div>

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
