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
        .money-table td,
        .totals-table td {
            border: 1px solid #e5e7eb;
            padding: 6px;
        }

        .attendance-table th {
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

        .money-table td:first-child {
            width: 72%;
        }

        .money-table td:last-child {
            width: 28%;
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

    $earningsLines = [
        ['label' => 'Basic Salary', 'amount' => (float) ($payroll->basic_salary ?? 0)],
        ['label' => 'Allied Health Council', 'amount' => (float) data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'ALLOWANCE_ALLIED_HEALTH_COUNCIL'), 'amount', $payroll->allowance_allied_health_council ?? 0)],
        ['label' => 'House Job', 'amount' => (float) data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'ALLOWANCE_HOUSE_JOB'), 'amount', $payroll->allowance_house_job ?? 0)],
        ['label' => 'Conveyance', 'amount' => (float) data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'ALLOWANCE_CONVEYANCE'), 'amount', $payroll->allowance_conveyance ?? 0)],
        ['label' => 'Medical', 'amount' => (float) data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'ALLOWANCE_MEDICAL'), 'amount', $payroll->allowance_medical ?? 0)],
        ['label' => 'House Rent', 'amount' => (float) data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'ALLOWANCE_HOUSE_RENT'), 'amount', $payroll->allowance_house_rent ?? 0)],
        ['label' => 'Sunday Roster Incentive', 'amount' => (float) data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'INCENTIVE_SUNDAY_ROSTER'), 'amount', $payroll->incentive_sunday_roster ?? 0)],
        ['label' => 'Home Visit Incentive', 'amount' => (float) data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'INCENTIVE_HOME_VISIT'), 'amount', $payroll->incentive_home_visit ?? 0)],
        ['label' => 'Speech Therapy Incentive', 'amount' => (float) data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'INCENTIVE_SPEECH_THERAPY'), 'amount', $payroll->incentive_speech_therapy ?? 0)],
        ['label' => 'Dry Needling Incentive', 'amount' => (float) data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'INCENTIVE_DRY_NEEDLING'), 'amount', $payroll->incentive_dry_needling ?? 0)],
        ['label' => 'Other Allowance', 'amount' => (float) data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'OTHER_ALLOWANCE'), 'amount', $payroll->other_allowance ?? 0)],
        ['label' => 'Awards / Bonus', 'amount' => (float) ($payroll->awards_total ?? $payroll->bonus ?? 0)],
    ];

    $deductionLines = [
        ['label' => 'Tax', 'amount' => (float) ($payroll->tax ?? 0)],
        ['label' => 'Provident Fund', 'amount' => (float) ($payroll->provident_fund ?? 0)],
        ['label' => 'EOBI', 'amount' => (float) ($payroll->eobi ?? 0)],
        ['label' => 'Advance', 'amount' => (float) ($payroll->advance ?? 0)],
        ['label' => 'Loan', 'amount' => (float) ($payroll->loan ?? 0)],
        ['label' => 'Absent Deduction', 'amount' => (float) ($payroll->absent_deduction ?? 0)],
        ['label' => 'Late Deduction', 'amount' => (float) ($payroll->late_deduction ?? 0)],
        ['label' => 'Other Deduction', 'amount' => (float) ($payroll->other_deduction ?? 0)],
    ];

    $totalEarnings = (float) (($payroll->calculated_salary ?? 0) + ($payroll->awards_total ?? 0));
    $totalDeductions = (float) ($payroll->deductions_total ?? 0);
    $netSalary = (float) ($payroll->final_salary ?? $payroll->final_settlement ?? 0);

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
    <p class="section-title">Attendance</p>
    <table class="attendance-table">
        <tr>
            <th>Working Days</th>
            <th>Present</th>
            <th>Absent</th>
            <th>Leaves</th>
            <th>Late Count</th>
            <th>Late Minutes</th>
            <th>OT Hours</th>
        </tr>
        <tr>
            <td>{{ $attendance['working_days'] ?? 0 }}</td>
            <td>{{ $attendance['present_days'] ?? 0 }}</td>
            <td>{{ $attendance['absent_days'] ?? 0 }}</td>
            <td>{{ $attendance['leave_days'] ?? 0 }}</td>
            <td>{{ $attendance['late_count'] ?? 0 }}</td>
            <td>{{ $attendance['late_minutes'] ?? 0 }}</td>
            <td>{{ number_format((float) ($attendance['overtime_hours'] ?? 0), 2) }}</td>
        </tr>
    </table>
    <div class="ot-note">Overtime is for record only - not included in salary.</div>
</div>

<table class="money-wrap">
    <tr>
        <td>
            <div class="card">
                <p class="section-title">Earnings</p>
                <table class="money-table">
                    @foreach($earningsLines as $line)
                        @if((float) $line['amount'] > 0)
                            <tr>
                                <td>{{ $line['label'] }}</td>
                                <td>{{ number_format((float) $line['amount'], 2) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </table>
            </div>
        </td>
        <td>
            <div class="card">
                <p class="section-title">Deductions</p>
                <table class="money-table">
                    @foreach($deductionLines as $line)
                        @if((float) $line['amount'] > 0)
                            <tr>
                                <td>{{ $line['label'] }}</td>
                                <td>{{ number_format((float) $line['amount'], 2) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </table>
            </div>
        </td>
    </tr>
</table>

<div class="card">
    <table class="totals-table">
        <tr>
            <td>Total Earnings</td>
            <td>{{ number_format($totalEarnings, 2) }}</td>
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
