@extends('layouts.app')
@section('title', 'Edit Doctor Availability')

@section('content')
<h2>Edit Availability for {{ $doctor->name }}</h2>

<form action="{{ route('doctors.availability.update', $availability->id) }}" method="POST">
    @csrf
    @method('PUT')

    <label>Date:</label>
    <input type="date" name="date" value="{{ $availability->date }}" required><br>

    <label>Start Time:</label>
    <input type="time" name="start_time" value="{{ $availability->start_time }}" required><br>

    <label>End Time:</label>
    <input type="time" name="end_time" value="{{ $availability->end_time }}" required><br>

    <label>Leave:</label>
    <input type="hidden" name="is_leave" value="0">
    <input type="checkbox" name="is_leave" value="1" {{ $availability->is_leave ? 'checked' : '' }}><br>

    <button type="submit" class="btn btn-primary">Update</button>
</form>
@endsection
