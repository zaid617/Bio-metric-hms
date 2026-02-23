@extends('layouts.app')
@section('title')
    Salary Records
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <!-- Bootstrap 5 CSS for Modal -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
@endpush
@section('content')
    <x-page-title title="Salaries" subtitle="Salary Records" />

    <div class="d-flex justify-content-between mb-3">
        <h6 class="mb-0 text-uppercase">All Salary Records</h6>
        <a href="/salaries/create" class="btn btn-primary">+ Add Employee Salary</a>
    </div>
    <hr>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="salaries-table" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Month</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Salary Info</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salaries as $salary)
                        <tr>
                            <td>{{ $salary->employee_name }}</td>
                            <td>{{ $salary->month }}</td>
                            <td>₨ {{ number_format($salary->net_salary) }}</td>
                            <td>
                                <span class="badge bg-{{ strtolower($salary->payment_status) === 'paid' ? 'success' : 'warning' }}">
                                    {{ ucfirst($salary->payment_status) }}
                                </span>
                            </td>
                            <td>
                                @if(strtolower($salary->payment_status) !== 'paid')
                                    <button type="button"
                                            class="btn btn-success btn-sm"
                                            data-id="{{ $salary->id }}"
                                            data-basic="{{ $salary->basic_salary }}"
                                            data-allowances="{{ $salary->allowances }}"
                                            onclick="openSalaryModal(this)">
                                        Mark as Paid
                                    </button>
                                @else
                                    <span class="badge bg-success">Paid</span>
                                @endif
                            </td>
                            <td>
                                <strong>Basic:</strong> ₨ {{ number_format($salary->basic_salary) }}<br>
                                <strong>Allowances:</strong> ₨ {{ number_format($salary->allowances) }}<br>
                                <strong>Bonus:</strong> ₨ {{ number_format($salary->bonuses) }}<br>
                                <strong>Deductions:</strong> ₨ {{ number_format($salary->deductions) }}<br>
                                <strong>Net:</strong> ₨ {{ number_format($salary->net_salary) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No salary records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-end"><strong>Total to Pay:</strong></td>
                            <td><strong>₨ {{ number_format($totalToPay) }}</strong></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Bonus & Deductions -->
    <div class="modal fade" id="salaryModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="salaryForm" method="POST" action="{{ route('salaries.markPaid') }}">
                @csrf
                <input type="hidden" name="salary_id" id="salary_id">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Finalize Salary Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-2">
                            <label>Bonus:</label>
                            <input type="number" name="bonuses" class="form-control" id="bonus_input" value="0">
                        </div>

                        <div class="mb-2">
                            <label>Deductions:</label>
                            <input type="number" name="deductions" class="form-control" id="deduction_input" value="0">
                        </div>

                        <div class="mb-2">
                            <strong>Net Salary:</strong>
                            <span id="net_salary_preview">0</span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Confirm & Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <!--plugins-->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#salaries-table').DataTable({
                responsive: true,
                ordering: true,
                searching: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                language: {
                    search: "",
                    searchPlaceholder: "Search salary records...",
                    lengthMenu: "_MENU_ records per page",
                },
                columnDefs: [
                    {
                        targets: [4, 5], // Action and Salary Info columns
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: [2, 3], // Net Salary and Status columns
                        width: "10%"
                    },
                    {
                        targets: [4], // Action column
                        width: "12%"
                    },
                    {
                        targets: [5], // Salary Info column
                        width: "20%"
                    }
                ],
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();
                    
                    // Remove the formatting to get integer data for summation
                    var intVal = function (i) {
                        return typeof i === 'string' ?
                            i.replace(/[\₨,]/g, '') * 1 :
                            typeof i === 'number' ?
                                i : 0;
                    };
                    
                    // Total over all pages
                    var total = api
                        .column(2)
                        .data()
                        .reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                    
                    // Total over this page
                    var pageTotal = api
                        .column(2, { page: 'current' })
                        .data()
                        .reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                    
                    // Update footer
                    $(api.column(2).footer()).html(
                        '₨ ' + pageTotal.toLocaleString() + ' (Page Total)'
                    );
                },
                createdRow: function(row, data, dataIndex) {
                    // Add some styling to improve readability
                    $(row).find('td').css('vertical-align', 'middle');
                }
            });
            
            // Add custom styling to the search box
            $('.dataTables_filter input').addClass('form-control form-control-sm');
        });

        function openSalaryModal(button) {
            const salaryId = button.getAttribute('data-id');
            const basic = parseFloat(button.getAttribute('data-basic'));
            const allowances = parseFloat(button.getAttribute('data-allowances'));

            document.getElementById('salary_id').value = salaryId;
            document.getElementById('bonus_input').value = 0;
            document.getElementById('deduction_input').value = 0;
            document.getElementById('net_salary_preview').innerText = (basic + allowances).toFixed(2);

            document.getElementById('bonus_input').oninput = updateNet;
            document.getElementById('deduction_input').oninput = updateNet;

            function updateNet() {
                const bonus = parseFloat(document.getElementById('bonus_input').value) || 0;
                const deduction = parseFloat(document.getElementById('deduction_input').value) || 0;
                const net = basic + allowances + bonus - deduction;
                document.getElementById('net_salary_preview').innerText = net.toFixed(2);
            }

            var myModal = new bootstrap.Modal(document.getElementById('salaryModal'));
            myModal.show();
        }
    </script>
    
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush