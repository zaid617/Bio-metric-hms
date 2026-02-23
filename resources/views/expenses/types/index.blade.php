@extends('layouts.app')

@section('title')
    Expense Types
@endsection

@push('css')
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/expenses.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Expense Types</h2>

    <!-- Add Expense Type Form -->
    <h3>Add New Expense Type</h3>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('expense.types.store') }}" method="POST" class="row g-2 mb-4">
        @csrf
        <div class="col-md-5">
            <input type="text" name="type" class="form-control" placeholder="Expense Type" required>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select" required>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-save">Save</button>
        </div>
    </form>

    <!-- Expense Types Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover" id="expenseTypesTable">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($types as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->type }}</td>
                    <td>{{ $t->status == 1 ? 'Active' : 'Inactive' }}</td>
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
            $('#expenseTypesTable').DataTable({
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

            // MetisMenu
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
