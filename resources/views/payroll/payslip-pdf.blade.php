<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip</title>
    <style>
        @page {
            margin: 16mm 12mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #1f2937;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.6px;
        }

        .sub {
            margin: 2px 0 0;
            color: #4b5563;
        }

        .warning {
            border: 1px solid #f59e0b;
            background: #fffbeb;
            color: #92400e;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 10px;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 3px 4px;
        }

        .info-grid .label {
            color: #6b7280;
            width: 38%;
            display: inline-block;
        }

        .card {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            margin: 0 0 8px;
            color: #111827;
        }

        .attendance-table,
        .money-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-table th,
        .attendance-table td,
        .money-table th,
        .money-table td,
        .totals-table td {
            border: 1px solid #e5e7eb;
            padding: 6px;
        }

        .attendance-table th,
        .money-table th {
            background: #f3f4f6;
            text-align: left;
        }

        .money-wrap {
            width: 100%;
            border-collapse: collapse;
        }

        .money-wrap td {
            width: 50%;
            vertical-align: top;
            padding: 0 4px;
        }

        .money-table td:first-child,
        .money-table th:first-child {
            width: 42%;
        }

        .money-table td:nth-child(2),
        .money-table th:nth-child(2) {
            width: 36%;
        }

        .money-table td:last-child,
        .money-table th:last-child {
            width: 22%;
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .ot-note {
            color: #6b7280;
            font-style: italic;
            font-size: 11px;
            margin-top: 6px;
        }

        .totals-table td {
            font-weight: 700;
        }

        .totals-table td:last-child {
            text-align: right;
        }

        .amount-words {
            margin-top: 6px;
            font-style: italic;
            color: #374151;
        }

        .footer {
            margin-top: 26px;
            font-size: 11px;
        }

        .sign-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .sign-wrap td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding-top: 26px;
        }

        .line {
            border-top: 1px solid #111827;
            width: 85%;
            margin: 0 auto 4px;
        }
    </style>
</head>
<body>
@php
    $employee = $payroll->employee;
    $attendance = $attendanceData ?? [];

    $earningsRows = collect($earningsBreakdown ?? $payroll->earnings_breakdown ?? [])
        ->map(fn ($line) => [
            'type' => (string) ($line['type'] ?? 'EARNING'),
            'amount' => (float) ($line['amount'] ?? 0),
            'notes' => (string) ($line['notes'] ?? ''),
        ])
        ->filter(fn ($line) => $line['amount'] > 0)
        ->values();

    $awardsRows = collect($awardsBreakdown ?? $payroll->awards_breakdown ?? [])
        ->map(fn ($line) => [
            'type' => (string) ($line['type'] ?? 'AWARD'),
            'amount' => (float) ($line['amount'] ?? 0),
            'notes' => (string) ($line['notes'] ?? ''),
        ])
        ->filter(fn ($line) => $line['amount'] > 0)
        ->values();

    $deductionRows = collect($deductionsBreakdown ?? $payroll->deductions_breakdown ?? [])
        ->map(fn ($line) => [
            'type' => (string) ($line['type'] ?? 'DEDUCTION'),
            'amount' => (float) ($line['amount'] ?? 0),
            'notes' => (string) ($line['notes'] ?? ''),
        ])
        ->filter(fn ($line) => $line['amount'] > 0)
        ->values();

    $allowanceTypes = [
        'ALLOWANCE_ALLIED_HEALTH_COUNCIL',
        'ALLOWANCE_HOUSE_JOB',
        'ALLOWANCE_CONVEYANCE',
        'ALLOWANCE_MEDICAL',
        'ALLOWANCE_HOUSE_RENT',
        'OTHER_ALLOWANCE',
    ];

    $allowanceRows = $earningsRows
        ->filter(fn ($line) => in_array($line['type'], $allowanceTypes, true))
        ->values();

    $incentiveRows = $earningsRows
        ->filter(fn ($line) => $line['type'] !== 'BASIC_SALARY' && !in_array($line['type'], $allowanceTypes, true))
        ->values();

    $basicSalaryTotal = (float) $earningsRows
        ->where('type', 'BASIC_SALARY')
        ->sum('amount');
    $allowancesTotal = (float) $allowanceRows->sum('amount');
    $incentivesTotal = (float) $incentiveRows->sum('amount');
    $awardsTotal = (float) $awardsRows->sum('amount');
    $grossPay = $basicSalaryTotal + $allowancesTotal + $incentivesTotal + $awardsTotal;
    $totalDeductions = (float) $deductionRows->sum('amount');
    $netSalary = (float) ($payroll->final_salary ?? $payroll->final_settlement ?? ($grossPay - $totalDeductions));

    $bankMasked = (string) data_get($payroll->payslip_data, 'employee.bank_masked', 'N/A');
    if ($bankMasked !== 'N/A') {
        $digits = preg_replace('/\D+/', '', $bankMasked);
        if ($digits !== '' && strlen($digits) > 4) {
            $bankMasked = str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
        }
    }
@endphp

<div class="header">
    <h1 class="title">Salary Payslip</h1>
    <p class="sub">Pay Period: {{ $periodLabel }}</p>
</div>

@if(!empty($warnings) && count($warnings) > 0)
    <div class="warning">
        @foreach($warnings as $warning)
            <div>{{ $warning }}</div>
        @endforeach
    </div>
@endif

<table class="info-grid">
    <tr>
        <td><span class="label">Employee ID:</span> {{ $employee->id ?? '-' }}</td>
        <td><span class="label">Name:</span> {{ $employee->name ?? '-' }}</td>
    </tr>
    <tr>
        <td><span class="label">Department:</span> {{ $employee->department ?? '-' }}</td>
        <td><span class="label">Designation:</span> {{ $employee->designation ?? '-' }}</td>
    </tr>
    <tr>
        <td><span class="label">Join Date:</span> {{ !empty($employee->joining_date) ? \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') : '-' }}</td>
        <td><span class="label">Bank:</span> {{ $bankMasked }}</td>
    </tr>
    <tr>
        <td><span class="label">Pay Period:</span> {{ $periodLabel }}</td>
        <td><span class="label">Generated:</span> {{ $generatedAt->format('d M Y H:i') }}</td>
    </tr>
</table>

<div class="card">
    <p class="section-title">Attendance Summary</p>
    <table class="attendance-table">
        <tr>
            <th>Working Days</th>
            <th>Present</th>
            <th>Absent</th>
            <th>Leaves</th>
            <th>Late Count</th>
        </tr>
        <tr>
            <td>{{ $attendance['working_days'] ?? 0 }}</td>
            <td>{{ $attendance['present_days'] ?? 0 }}</td>
            <td>{{ $attendance['absent_days'] ?? 0 }}</td>
            <td>{{ $attendance['leave_days'] ?? 0 }}</td>
            <td>{{ $attendance['late_count'] ?? 0 }}</td>
        </tr>
    </table>
</div>

<div class="card">
    <p class="section-title">Allowances (Auto Added from Employee Profile)</p>
    <table class="money-table">
        <thead>
            <tr>
                <th>Allowance</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($allowanceRows as $line)
                <tr>
                    <td>{{ str_replace('_', ' ', $line['type']) }}</td>
                    <td>{{ number_format((float) $line['amount'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="muted">No allowances for this payroll.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <p class="section-title">Incentives</p>
    <table class="money-table">
        <thead>
            <tr>
                <th>Incentive</th>
                <th>Notes</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incentiveRows as $line)
                <tr>
                    <td>{{ str_replace('_', ' ', $line['type']) }}</td>
                    <td class="muted">{{ $line['notes'] !== '' ? $line['notes'] : '-' }}</td>
                    <td>{{ number_format((float) $line['amount'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="muted">No incentives for this payroll.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<table class="money-wrap">
    <tr>
        <td>
            <div class="card">
                <p class="section-title">Awards / Rewards</p>
                <table class="money-table">
                    <thead>
                        <tr>
                            <th>Award</th>
                            <th>Notes</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($awardsRows as $line)
                            <tr>
                                <td>{{ str_replace('_', ' ', $line['type']) }}</td>
                                <td class="muted">{{ $line['notes'] !== '' ? $line['notes'] : '-' }}</td>
                                <td>{{ number_format((float) $line['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="muted">No awards for this payroll.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </td>
        <td>
            <div class="card">
                <p class="section-title">Deductions</p>
                <table class="money-table">
                    <thead>
                        <tr>
                            <th>Deduction</th>
                            <th>Notes</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deductionRows as $line)
                            <tr>
                                <td>{{ str_replace('_', ' ', $line['type']) }}</td>
                                <td class="muted">{{ $line['notes'] !== '' ? $line['notes'] : '-' }}</td>
                                <td>{{ number_format((float) $line['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="muted">No deductions for this payroll.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </td>
    </tr>
</table>

<div class="card">
    <table class="totals-table">
        <tr>
            <td>Base Salary</td>
            <td>{{ number_format($basicSalaryTotal, 2) }}</td>
        </tr>
        <tr>
            <td>Total Allowances</td>
            <td>{{ number_format($allowancesTotal, 2) }}</td>
        </tr>
        <tr>
            <td>Total Incentives</td>
            <td>{{ number_format($incentivesTotal, 2) }}</td>
        </tr>
        <tr>
            <td>Total Awards</td>
            <td>{{ number_format($awardsTotal, 2) }}</td>
        </tr>
        <tr>
            <td>Gross Pay (Base + Allowances + Incentives + Awards)</td>
            <td>{{ number_format($grossPay, 2) }}</td>
        </tr>
        <tr>
            <td>Total Deductions</td>
            <td>{{ number_format($totalDeductions, 2) }}</td>
        </tr>
        <tr>
            <td>Net Salary</td>
            <td>{{ number_format($netSalary, 2) }}</td>
        </tr>
    </table>
    <div class="amount-words">Amount in words: {{ $amountInWords }}</div>
</div>

<div class="footer">
    <table class="sign-wrap">
        <tr>
            <td>
                <div class="line"></div>
                Employee Signature
            </td>
            <td>
                <div class="line"></div>
                HR/Accounts Signature
            </td>
            <td>
                <div class="line"></div>
                Authorized Signature
            </td>
        </tr>
    </table>
    <p class="muted" style="margin-top: 14px;">System generated document</p>
</div>
</body>
</html>
