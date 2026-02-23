@extends('layouts.app')

@section('title', 'Doctor Availability')

@section('content')
<div class="container mt-4">

    <h2 class="mb-4">
        Doctor Availability -
        <span class="text-primary">{{ $doctor->name }}</span>
    </h2>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Month Navigation --}}
    <div class="d-flex justify-content-between mb-3">
        <a href="{{ route('doctors.availability.index', [
            'doctor' => $doctor->id,
            'month' => $month-1 < 1 ? 12 : $month-1,
            'year'  => $month-1 < 1 ? $year-1 : $year
        ]) }}" class="btn btn-outline-secondary">
            &larr; Previous Month
        </a>

        <h4>{{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</h4>

        <a href="{{ route('doctors.availability.index', [
            'doctor' => $doctor->id,
            'month' => $month+1 > 12 ? 1 : $month+1,
            'year'  => $month+1 > 12 ? $year+1 : $year
        ]) }}" class="btn btn-outline-secondary">
            Next Month &rarr;
        </a>
    </div>

    {{-- Schedule Table --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">Schedule</div>

        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Morning</th>
                        <th>Evening</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($datesInMonth as $date)
                        @php $avail = $availabilities[$date] ?? null; @endphp
                        <tr>
                            <td>{{ $date }}</td>
                            <td>{{ \Carbon\Carbon::parse($date)->format('l') }}</td>
                            <td>
                                @if($avail?->morning_leave)
                                    <span class="badge bg-danger">Leave</span>
                                @else
                                    {{ $avail?->morning_start ?? '-' }}
                                    -
                                    {{ $avail?->morning_end ?? '-' }}
                                @endif
                            </td>
                            <td>
                                @if($avail?->evening_leave)
                                    <span class="badge bg-danger">Leave</span>
                                @else
                                    {{ $avail?->evening_start ?? '-' }}
                                    -
                                    {{ $avail?->evening_end ?? '-' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Toggle Button --}}
    <div class="text-center mb-4">
        <button class="btn btn-lg btn-outline-primary px-5"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#setForm">
            + Set / Update Availability
        </button>
    </div>

    {{-- FORM (DEFAULT CLOSED) --}}
    <div class="collapse" id="setForm">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                Set / Update Availability
            </div>

            <div class="card-body">
                <form method="POST"
                      action="{{ route('doctors.availability.store', $doctor->id) }}">
                    @csrf

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Morning Start</th>
                                    <th>Morning End</th>
                                    <th>Morning Leave</th>
                                    <th>Evening Start</th>
                                    <th>Evening End</th>
                                    <th>Evening Leave</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($datesInMonth as $date)
                                    @php
                                        $avail = $availabilities[$date] ?? null;
                                        $day = \Carbon\Carbon::parse($date)->format('l');
                                    @endphp
                                    <tr>
                                        <td>{{ $date }}</td>
                                        <td>{{ $day }}</td>

                                        <td>
                                            <input type="time"
                                                   name="morning_start[{{ $date }}]"
                                                   value="{{ $avail?->morning_start }}"
                                                   class="form-control form-control-sm">
                                        </td>

                                        <td>
                                            <input type="time"
                                                   name="morning_end[{{ $date }}]"
                                                   value="{{ $avail?->morning_end }}"
                                                   class="form-control form-control-sm">
                                        </td>

                                        <td class="text-center">
                                            <input type="checkbox"
                                                   name="morning_leave[{{ $date }}]"
                                                   @checked($avail?->morning_leave)>
                                        </td>

                                        <td>
                                            <input type="time"
                                                   name="evening_start[{{ $date }}]"
                                                   value="{{ $avail?->evening_start }}"
                                                   class="form-control form-control-sm">
                                        </td>

                                        <td>
                                            <input type="time"
                                                   name="evening_end[{{ $date }}]"
                                                   value="{{ $avail?->evening_end }}"
                                                   class="form-control form-control-sm">
                                        </td>

                                        <td class="text-center">
                                            <input type="checkbox"
                                                   name="evening_leave[{{ $date }}]"
                                                   @checked($avail?->evening_leave)>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="btn btn-success">
                        💾 Save Availability
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="d-flex gap-3 mb-4">
        <form method="POST"
              action="{{ route('doctors.availability.generateNextMonth', $doctor->id) }}"
              class="flex-fill">
            @csrf
            <button class="btn btn-info w-100">
                📅 Generate Next Month
            </button>
        </form>

        <form method="POST"
              action="{{ route('doctors.availability.deleteMonth', $doctor->id) }}"
              class="flex-fill">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger w-100">
                🗑 Delete Current Month
            </button>
        </form>
    </div>

</div>
@endsection
