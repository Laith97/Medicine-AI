<div class="modal fade" id="composeModal" tabindex="-1" aria-labelledby="composeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="composeModalLabel">New Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('patient.messages.store') }}" method="POST" enctype="multipart/form-data" id="composeForm">
                @csrf
                <div class="modal-body">
                    @php
                        $eligibleDoctors = app(\App\Services\MessagingService::class)->getEligibleDoctorsForPatient(auth()->id());
                    @endphp

                    @if($eligibleDoctors->isEmpty())
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            You don't have any doctors to message yet. Please schedule an appointment first.
                        </div>
                    @else
                        <input type="hidden" name="diagnosis_id" id="diagnosis_id" value="{{ $prefill['diagnosis_id'] ?? '' }}">

                        <div class="mb-3">
                            <label for="doctor_id" class="form-label">To Doctor</label>
                            <select name="doctor_id" id="doctor_id" class="form-select" required>
                                <option value="">Select a doctor</option>
                                @foreach($eligibleDoctors as $doctor)
                                    <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" name="subject" id="subject" class="form-control" required maxlength="255" placeholder="What is this about?">
                        </div>

                        <div class="mb-3">
                            <label for="body" class="form-label">Message</label>
                            <textarea name="body" id="body" class="form-control" rows="5" required maxlength="5000" placeholder="Type your message here..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="attachments" class="form-label">Attachments (optional)</label>
                            <input type="file" name="attachments[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.txt">
                            <small class="text-muted">Max 10MB per file.</small>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if(!$eligibleDoctors->isEmpty())
                        <button type="submit" class="btn btn-primary" id="sendMessageBtn">
                            <i class="fas fa-paper-plane me-1"></i>Send Message
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('composeForm');
    if (form) {
        form.addEventListener('submit', function() {
            var btn = document.getElementById('sendMessageBtn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Sending...';
            }
        });
    }
});
</script>
@endpush
