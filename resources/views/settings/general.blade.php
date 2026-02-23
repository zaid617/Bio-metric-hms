@extends('layouts.app')
@section('title')
    Branch Fee Settings
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/bs-stepper/css/bs-stepper.css') }}" rel="stylesheet">
    <style>
        .fee-table {
            width: 100%;
            border-collapse: collapse;
        }
        .fee-table th, .fee-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .fee-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .fee-input {
            width: 150px;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
    </style>
@endpush
@section('content')

    <x-page-title title="Branch" subtitle="Fee Settings" />

    <div class="card">
        <div class="card-body">
            <h5 class="mb-4">Set Checkup Fee for Each Branch</h5>

            <form action="/settings/general" method="POST">
                @csrf
                <div class="table-responsive">
                    <table class="fee-table">
                        <thead>
                            <tr>
                                <th>Branch Name</th>
                                <th>Checkup Fee</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branches as $branch)
                            <tr>
                                <td>{{ $branch->name }}</td>
                                <td>
                                    <input type="number" step="0.01" name="fees[{{ $branch->id }}]" 
                                           value="{{ optional($branch->generalSetting)->default_checkup_fee }}"
                                           class="form-control fee-input">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-info px-4">Update Fees</button>
                </div>
            </form>
        </div>
    </div>

@endsection
@push('script')
    <!--plugins-->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
