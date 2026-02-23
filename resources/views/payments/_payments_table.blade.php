<h6>Appointments / Checkups</h6>
<table class="table table-bordered table-hover">
<thead class="table-dark">
<tr>
<th>Patient</th><th>Date</th><th>Fee</th><th>Paid</th><th>Return</th>
</tr>
</thead>
<tbody>
@foreach($checkups as $c)
<tr>
<td>{{ $c->patient->name ?? '-' }}</td>
<td>{{ \Carbon\Carbon::parse($c->created_at)->format('d-m-Y') }}</td>
<td>{{ number_format($c->fee,2) }}</td>
<td>{{ number_format($c->paid_amount ?? 0,2) }}</td>
<td>
  <a href="{{ route('consultations.print.custom', $c->id) }}" class="btn btn-sm btn-danger">
    Return
  </a>
</td>
</tr>
@endforeach
</tbody>
</table>

<h6>Treatment Sessions</h6>
<table class="table table-bordered table-hover">
<thead class="table-dark">
<tr>
<th>Patient</th><th>Date</th><th>Total Fee</th><th>Paid</th><th>Return</th><th>Payments Receivable</th>
</tr>
</thead>
<tbody>
@foreach($treatments as $t)
<tr>
<td>{{ $t->patient->name ?? '-' }}</td>
<td>{{ \Carbon\Carbon::parse($t->created_at)->format('d-m-Y') }}</td>
<td>{{ number_format($t->session_fee,2) }}</td>
<td>{{ number_format($t->paid_amount ?? 0,2) }}</td>
<td>
  <a href="{{ route('invoice.ledgerr', $t->id) }}" class="btn btn-sm btn-danger">
    Return
  </a>
</td>
<td>
  <a href="{{ route('invoice.ledger', $t->id) }}" class="btn btn-sm btn-success">
    Payments Receivable
  </a>
</td>




{{--<td>{{ number_format($t->dues_amount ?? $t->session_fee - ($t->paid_amount ?? 0),2) }}</td>--}}
</tr>
@endforeach
</tbody>
</table>
