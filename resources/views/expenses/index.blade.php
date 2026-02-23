@extends('layouts.app')

@section('title')
    Expenses List
@endsection

@push('css')
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/expenses.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">All Expenses
        <a href="{{ route('expenses.create') }}" class="btn btn-success btn-add">Add New Expense</a>
    </h2>

    <!-- Success Message -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover" id="expensesTable">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Expense Type</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Remarks</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $e)
                <tr>
                    <td>{{ $e->id }}</td>
                    <td>{{ $e->expense_type }}</td>
                    <td>{{ $e->amount }}</td>
                    <td>{{ $e->method }}</td>
                    <td>{{ $e->remarks }}</td>
                    <td>{{ \Carbon\Carbon::parse($e->created_at)->format('d-m-Y H:i:s') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('script')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Core Plugins -->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#expensesTable').DataTable({
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
            });

            // Perfect Scrollbar
            const container = document.querySelector('.table-responsive');
            if(container){
                new PerfectScrollbar(container);
            }

            // MetisMenu (side menu)
            if($("#menu").length){
                $("#menu").metisMenu();
            }

            // Input Tags
            $('input[data-role=tagsinput]').tagsinput();

            // SimpleBar
            $('[data-simplebar]').each(function(){
                new SimpleBar(this);
            });
        });
    </script>
@endpush
