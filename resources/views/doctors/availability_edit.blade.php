<h3>Edit Availability for {{ $availability->doctor->name ?? 'Doctor' }}</h3>

<form action="{{ route('doctors.availability.update', $availability->id) }}" method="POST">
    @csrf
    @method('PUT')

    <label>Date:</label>
    <input type="date" name="date" value="{{ $availability->date }}" required><br><br>

    <label>
        <input type="checkbox" name="is_leave" id="is_leave" value="1" 
            {{ $availability->start_time == null && $availability->end_time == null ? 'checked' : '' }}>
        Mark as Leave
    </label>
    <br><br>

    <label>Start Time:</label>
    <input type="time" name="start_time" id="start_time" 
           value="{{ $availability->start_time }}" 
           {{ $availability->start_time == null ? 'disabled' : '' }}>
    <br><br>

    <label>End Time:</label>
    <input type="time" name="end_time" id="end_time" 
           value="{{ $availability->end_time }}" 
           {{ $availability->end_time == null ? 'disabled' : '' }}>
    <br><br>

    <button type="submit">Update Slot</button>
</form>

<script>
    // Toggle start/end time disable when leave checkbox clicked
    document.getElementById('is_leave').addEventListener('change', function () {
        const start = document.getElementById('start_time');
        const end = document.getElementById('end_time');
        if (this.checked) {
            start.disabled = true;
            end.disabled = true;
            start.value = "";
            end.value = "";
        } else {
            start.disabled = false;
            end.disabled = false;
        }
    });
</script>

