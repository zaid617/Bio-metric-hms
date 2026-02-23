@extends('layouts.app')

@section('title')
    Banks
@endsection

@push('css')
    {{-- DataTables CSS --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        /* Allow dropdown to overflow table container */
        .table-responsive {
            overflow: visible;
        }
    </style>
@endpush

@section('content')
    <x-page-title title="Banks" subtitle="Management" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <div class="card">
                <div class="card-body">

                    <!-- Header with Add New Button -->
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="mb-0">All Banks</h5>
                        <a href="{{ route('banks.create') }}" class="btn btn-primary">Add New Bank</a>
                    </div>

                    <!-- Banks Table -->
                    <div class="table-responsive">
                        <table id="banksTable" class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Bank Name</th>
                                    <th>Account No</th>
                                    <th>Account Title</th>
                                    <th>Balance</th>
                                    <th style="width:200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($banks as $bank)
                                    <tr>
                                        <td>{{ $bank->id }}</td>
                                        <td>{{ $bank->bank_name }}</td>
                                        <td>{{ $bank->account_no }}</td>
                                        <td>{{ $bank->account_title }}</td>
                                        <td>{{ number_format($bank->balance, 2) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-primary">Actions</button>
                                                <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span class="visually-hidden">Toggle Dropdown</span>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:220px;">
                                                    <a href="{{ route('banks.show', $bank->id) }}" class="btn btn-sm btn-info mb-1 w-100">View</a>
                                                    <a href="{{ route('banks.edit', $bank->id) }}" class="btn btn-sm btn-warning mb-1 w-100">Edit</a>
                                                    <form action="{{ route('banks.destroy', $bank->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this bank?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger mb-1 w-100">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No banks found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- End table -->

                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    {{-- jQuery first --}}
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    {{-- Core Plugins --}}
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <script>
        $(document).ready(function () {
            $('#banksTable').DataTable({
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                ordering: true,
                columnDefs: [
                    { orderable: false, targets: 5 } // Actions column
                ]
            });
        });
    </script>
@endpush
