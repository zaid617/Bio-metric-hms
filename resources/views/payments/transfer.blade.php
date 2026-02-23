@extends('layouts.app')

@section('title')
    Funds Transfer
@endsection

@push('css')
    {{-- DataTables CSS (optional future use) --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        .table-responsive {
            overflow: visible; /* Dropdown fix if needed */
        }
    </style>
@endpush

@section('content')
<div class="container">
    <h3 class="mb-4 text-center">💸 Funds Transfer</h3>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success text-center">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger text-center">{{ session('error') }}</div>
    @endif

    <form id="transferForm" action="{{ route('transfer.store') }}" method="POST" class="border p-4 rounded shadow-sm bg-light">
        @csrf

        {{-- FROM SECTION --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">From Type:</label>
                <select name="from_type" id="from_type" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="bank">Bank</option>
                    <option value="branch">Branch</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">From Account:</label>
                <select name="from_id" id="from_id" class="form-select" required>
                    <option value="">Select Account</option>
                </select>
            </div>
        </div>

        {{-- AMOUNT --}}
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label fw-bold">Amount:</label>
                <input type="number" name="amount" required min="1" class="form-control" placeholder="Enter amount" />
            </div>
        </div>

        {{-- TO SECTION --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">To Type:</label>
                <select name="to_type" id="to_type" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="bank">Bank</option>
                    <option value="branch">Branch</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">To Account:</label>
                <select name="to_id" id="to_id" class="form-select" required>
                    <option value="">Select Account</option>
                </select>
            </div>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary px-5">💸 Transfer Funds</button>
        </div>
    </form>
</div>
@endsection

@push('script')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS (optional future use) -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Core Plugins -->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <!-- Custom JS for Funds Transfer -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const banks = @json($banks);
        const branches = @json($branches);

        function populateAccounts(typeSelect, idSelect) {
            const type = document.getElementById(typeSelect).value;
            const select = document.getElementById(idSelect);
            select.innerHTML = '<option value="">Select Account</option>';

            const data = type === 'bank' ? banks : branches;

            data.forEach(item => {
                const name = item.bank_name || item.name;
                const balance = parseFloat(item.balance).toLocaleString();
                select.innerHTML += `
                    <option value="${item.id}" data-balance="${item.balance}">
                        ${name}&emsp;&emsp;&emsp;Balance: ${balance}
                    </option>`;
            });
        }

        document.getElementById('from_type').addEventListener('change', () => populateAccounts('from_type', 'from_id'));
        document.getElementById('to_type').addEventListener('change', () => populateAccounts('to_type', 'to_id'));

        // Prevent same account transfer
        document.getElementById('transferForm').addEventListener('submit', function(e) {
            const fromType = document.getElementById('from_type').value;
            const fromId = document.getElementById('from_id').value;
            const toType = document.getElementById('to_type').value;
            const toId = document.getElementById('to_id').value;

            if (fromType === toType && fromId === toId) {
                e.preventDefault();
                alert('From and To accounts cannot be the same!');
                return false;
            }
        });
    });
    </script>
@endpush
