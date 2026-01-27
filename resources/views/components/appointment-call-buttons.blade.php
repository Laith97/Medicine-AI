{{-- Appointment Call/Video Buttons Component --}}

@if($appointment->appointment_type === 'phone_call' && $appointment->status === 'confirmed')
    <button onclick="showPatientPhone({{ $appointment->id }})" class="btn btn-success" id="phone-btn-{{ $appointment->id }}">
        <i class="fas fa-phone"></i> Show Patient Phone
    </button>
    <div id="phone-display-{{ $appointment->id }}" style="display: none; margin-top: 10px;">
        <div class="alert alert-info">
            <strong>Patient:</strong> <span id="patient-name-{{ $appointment->id }}"></span><br>
            <strong>Phone:</strong> <a href="#" id="patient-phone-{{ $appointment->id }}" style="font-size: 1.2em; font-weight: bold;"></a>
        </div>
    </div>
@endif

@if($appointment->appointment_type === 'video_call' && $appointment->status === 'confirmed')
    <a href="{{ route('video.room', $appointment->id) }}" class="btn btn-primary" target="_blank">
        <i class="fas fa-video"></i> Start Video Call
    </a>
@endif

@push('scripts')
<script>
function showPatientPhone(appointmentId) {
    const btn = document.getElementById(`phone-btn-${appointmentId}`);
    const display = document.getElementById(`phone-display-${appointmentId}`);
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    
    fetch(`/api/appointments/${appointmentId}/patient-phone`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById(`patient-name-${appointmentId}`).textContent = data.patient_name;
            const phoneLink = document.getElementById(`patient-phone-${appointmentId}`);
            phoneLink.textContent = data.phone;
            phoneLink.href = `tel:${data.phone}`;
            
            btn.style.display = 'none';
            display.style.display = 'block';
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-phone"></i> Show Patient Phone';
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.error || 'Unable to get patient phone'
                });
            } else {
                alert('Error: ' + (data.error || 'Unable to get patient phone'));
            }
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-phone"></i> Show Patient Phone';
        console.error('Error:', error);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to get patient phone. Please try again.'
            });
        } else {
            alert('Failed to get patient phone. Please try again.');
        }
    });
}
</script>
@endpush
