@extends('layouts.app')

@section('title')
    Add Expense
@endsection

@push('css')
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/expenses.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Add New Expense</h2>

    <!-- Success Message -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('expenses.store') }}" method="POST">
        @csrf

        <!-- Branch -->
        <div class="mb-3">
            <label for="branch_id" class="form-label">Branch</label>
            @if(auth()->user()->hasRole('admin'))
                <select name="branch_id" id="branch_id" class="form-select" required>
                    <option value="">-- Select Branch --</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            @else
                <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                <input type="text" class="form-control" value="{{ auth()->user()->branch->name }}" readonly>
            @endif
        </div>

        <!-- Expense Type -->
        <div class="mb-3">
            <label for="expense_type_id" class="form-label">Expense Type</label>
            <select name="expense_type_id" id="expense_type_id" class="form-select" required>
                <option value="">-- Select Expense Type --</option>
                @foreach($types as $t)
                    <option value="{{ $t->id }}">{{ $t->type }}</option>
                @endforeach
            </select>
        </div>

        <!-- Amount -->
        <div class="mb-3">
            <label for="amount" class="form-label">Amount</label>
            <input type="number" name="amount" id="amount" step="0.01"
                   class="form-control" placeholder="Enter amount" required>
        </div>

        <!-- Payment Method -->
        <div class="mb-3">
            <label for="method" class="form-label">Payment Method</label>
            <select name="method" id="method" class="form-select" required>
                <option value="">-- Select Method --</option>
                <option value="Cash">Cash</option>
                <option value="Bank">Bank</option>
            </select>
        </div>

        <!-- Remarks -->
        <div class="mb-3">
            <label for="remarks" class="form-label">Remarks</label>
            <textarea name="remarks" id="remarks" rows="3" class="form-control" placeholder="Optional"></textarea>
        </div>

        <button type="submit" class="btn btn-primary me-2">Save Expense</button>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Back to List</a>
    </form>
</div>
@endsection

@push('script')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Core Plugins -->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Perfect Scrollbar
            const container = document.querySelector('.container');
            if(container){
                new PerfectScrollbar(container);
            }

            // MetisMenu (side menu if present)
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
