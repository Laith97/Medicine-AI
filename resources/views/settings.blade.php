@extends('master')

@section('title', 'Settings')

@section('content')
<div class="main-content">
    <div class="layout-px-spacing">
        <div class="middle-content container-xxl p-0">

            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <form action="{{ route('settings.update') }}" method="POST" class="bg-white p-4 rounded shadow-sm">
                            @csrf
                            @method('PUT')

                            <h5 class="mb-4 text-center">Select Criterion for Symptom Evaluation</h5>

                            <div class="mb-3 text-center">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="nice" name="criterion" value="NICE"
                                        {{ ($setting && $setting->criterion == 'NICE') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="nice">NICE</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="cdc" name="criterion" value="CDC"
                                        {{ (!$setting || $setting->criterion == 'CDC') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cdc">CDC</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="mayo_clinic" name="criterion" value="Mayo Clinic"
                                        {{ ($setting && $setting->criterion == 'Mayo Clinic') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="mayo_clinic">Mayo Clinic</label>
                                </div>
                            </div>

                            <h5 class="mb-4 mt-5 text-center">Select Your Medical Specialty</h5>

                            <div class="mb-3">
                                <select class="form-select" name="specialty_select" id="specialty_select" onchange="toggleCustomSpecialtySettings()">
                                    <option value="" {{ (!$setting || !$setting->specialty) ? 'selected' : '' }}>-- Select Specialty --</option>

                                    <optgroup label="🧠 General & Internal Medicine">
                                        <option value="General Practitioner" {{ ($setting && $setting->specialty == 'General Practitioner') ? 'selected' : '' }}>General Practitioner (GP) / Family Medicine</option>
                                        <option value="Internal Medicine" {{ ($setting && $setting->specialty == 'Internal Medicine') ? 'selected' : '' }}>Internal Medicine (Internist)</option>
                                    </optgroup>

                                    <optgroup label="🩺 Internal Medicine Subspecialties">
                                        <option value="Cardiology" {{ ($setting && $setting->specialty == 'Cardiology') ? 'selected' : '' }}>Cardiology (Heart)</option>
                                        <option value="Pulmonology" {{ ($setting && $setting->specialty == 'Pulmonology') ? 'selected' : '' }}>Pulmonology (Lungs)</option>
                                        <option value="Gastroenterology" {{ ($setting && $setting->specialty == 'Gastroenterology') ? 'selected' : '' }}>Gastroenterology (Digestive system)</option>
                                        <option value="Nephrology" {{ ($setting && $setting->specialty == 'Nephrology') ? 'selected' : '' }}>Nephrology (Kidneys)</option>
                                        <option value="Endocrinology" {{ ($setting && $setting->specialty == 'Endocrinology') ? 'selected' : '' }}>Endocrinology (Hormones & glands)</option>
                                        <option value="Hematology" {{ ($setting && $setting->specialty == 'Hematology') ? 'selected' : '' }}>Hematology (Blood)</option>
                                        <option value="Hematology-Oncology" {{ ($setting && $setting->specialty == 'Hematology-Oncology') ? 'selected' : '' }}>Hematology-Oncology (Blood cancers)</option>
                                        <option value="Rheumatology" {{ ($setting && $setting->specialty == 'Rheumatology') ? 'selected' : '' }}>Rheumatology (Joints & autoimmune diseases)</option>
                                        <option value="Infectious Disease" {{ ($setting && $setting->specialty == 'Infectious Disease') ? 'selected' : '' }}>Infectious Disease</option>
                                        <option value="Dermatology" {{ ($setting && $setting->specialty == 'Dermatology') ? 'selected' : '' }}>Dermatology (Skin, hair, nails)</option>
                                        <option value="Allergy & Immunology" {{ ($setting && $setting->specialty == 'Allergy & Immunology') ? 'selected' : '' }}>Allergy & Immunology</option>
                                        <option value="Reproductive Endocrinology" {{ ($setting && $setting->specialty == 'Reproductive Endocrinology') ? 'selected' : '' }}>Reproductive Endocrinology (Fertility hormones)</option>
                                    </optgroup>

                                    <optgroup label="🧠 Emergency & Critical Care">
                                        <option value="Emergency Medicine" {{ ($setting && $setting->specialty == 'Emergency Medicine') ? 'selected' : '' }}>Emergency Medicine</option>
                                        <option value="Critical Care" {{ ($setting && $setting->specialty == 'Critical Care') ? 'selected' : '' }}>Critical Care / Intensive Care Medicine</option>
                                    </optgroup>

                                    <optgroup label="💉 Anesthesia & Pain Management">
                                        <option value="Anesthesiology" {{ ($setting && $setting->specialty == 'Anesthesiology') ? 'selected' : '' }}>Anesthesiology</option>
                                        <option value="Pain Management" {{ ($setting && $setting->specialty == 'Pain Management') ? 'selected' : '' }}>Pain Management / Interventional Pain Medicine</option>
                                    </optgroup>

                                    <optgroup label="🧠 Neurology & Psychiatry">
                                        <option value="Neurology" {{ ($setting && $setting->specialty == 'Neurology') ? 'selected' : '' }}>Neurology (Brain & nerves)</option>
                                        <option value="Neurosurgery" {{ ($setting && $setting->specialty == 'Neurosurgery') ? 'selected' : '' }}>Neurosurgery (Brain & spine surgery)</option>
                                        <option value="Psychiatry" {{ ($setting && $setting->specialty == 'Psychiatry') ? 'selected' : '' }}>Psychiatry (Mental health)</option>
                                        <option value="Child & Adolescent Psychiatry" {{ ($setting && $setting->specialty == 'Child & Adolescent Psychiatry') ? 'selected' : '' }}>Child & Adolescent Psychiatry</option>
                                        <option value="Behavioral & Developmental Pediatrics" {{ ($setting && $setting->specialty == 'Behavioral & Developmental Pediatrics') ? 'selected' : '' }}>Behavioral & Developmental Pediatrics</option>
                                    </optgroup>

                                    <optgroup label="🦴 Surgical Specialties">
                                        <option value="General Surgery" {{ ($setting && $setting->specialty == 'General Surgery') ? 'selected' : '' }}>General Surgery</option>
                                        <option value="Orthopedic Surgery" {{ ($setting && $setting->specialty == 'Orthopedic Surgery') ? 'selected' : '' }}>Orthopedic Surgery (Bones & joints)</option>
                                        <option value="Cardiothoracic Surgery" {{ ($setting && $setting->specialty == 'Cardiothoracic Surgery') ? 'selected' : '' }}>Cardiothoracic Surgery (Heart & lungs)</option>
                                        <option value="Vascular Surgery" {{ ($setting && $setting->specialty == 'Vascular Surgery') ? 'selected' : '' }}>Vascular Surgery (Blood vessels)</option>
                                        <option value="Pediatric Vascular Surgery" {{ ($setting && $setting->specialty == 'Pediatric Vascular Surgery') ? 'selected' : '' }}>Pediatric Vascular Surgery</option>
                                        <option value="Plastic & Reconstructive Surgery" {{ ($setting && $setting->specialty == 'Plastic & Reconstructive Surgery') ? 'selected' : '' }}>Plastic & Reconstructive Surgery</option>
                                        <option value="Oral & Maxillofacial Surgery" {{ ($setting && $setting->specialty == 'Oral & Maxillofacial Surgery') ? 'selected' : '' }}>Oral & Maxillofacial Surgery</option>
                                        <option value="Surgical Oncology" {{ ($setting && $setting->specialty == 'Surgical Oncology') ? 'selected' : '' }}>Surgical Oncology (Cancer surgery)</option>
                                        <option value="Colorectal Surgery" {{ ($setting && $setting->specialty == 'Colorectal Surgery') ? 'selected' : '' }}>Colorectal Surgery</option>
                                        <option value="Urology" {{ ($setting && $setting->specialty == 'Urology') ? 'selected' : '' }}>Urology (Urinary & male reproductive system)</option>
                                        <option value="ENT" {{ ($setting && $setting->specialty == 'ENT') ? 'selected' : '' }}>ENT / Otolaryngology (Ear, Nose, Throat)</option>
                                        <option value="Ophthalmic Surgery" {{ ($setting && $setting->specialty == 'Ophthalmic Surgery') ? 'selected' : '' }}>Ophthalmic Surgery (Eye surgery)</option>
                                        <option value="Pediatric Surgery" {{ ($setting && $setting->specialty == 'Pediatric Surgery') ? 'selected' : '' }}>Pediatric Surgery</option>
                                        <option value="Hand Surgery" {{ ($setting && $setting->specialty == 'Hand Surgery') ? 'selected' : '' }}>Hand Surgery</option>
                                    </optgroup>

                                    <optgroup label="👶 Pediatrics & Women's Health">
                                        <option value="Pediatrics" {{ ($setting && $setting->specialty == 'Pediatrics') ? 'selected' : '' }}>Pediatrics</option>
                                        <option value="Neonatology" {{ ($setting && $setting->specialty == 'Neonatology') ? 'selected' : '' }}>Neonatology (Newborn care)</option>
                                        <option value="Pediatric Behavioral Medicine" {{ ($setting && $setting->specialty == 'Pediatric Behavioral Medicine') ? 'selected' : '' }}>Pediatric Behavioral Medicine</option>
                                        <option value="Obstetrics & Gynecology" {{ ($setting && $setting->specialty == 'Obstetrics & Gynecology') ? 'selected' : '' }}>Obstetrics & Gynecology (OB/GYN)</option>
                                        <option value="Gynecologic Oncology" {{ ($setting && $setting->specialty == 'Gynecologic Oncology') ? 'selected' : '' }}>Gynecologic Oncology</option>
                                        <option value="Reproductive Endocrinology & Infertility" {{ ($setting && $setting->specialty == 'Reproductive Endocrinology & Infertility') ? 'selected' : '' }}>Reproductive Endocrinology & Infertility</option>
                                        <option value="Maternal–Fetal Medicine" {{ ($setting && $setting->specialty == 'Maternal–Fetal Medicine') ? 'selected' : '' }}>Maternal–Fetal Medicine</option>
                                    </optgroup>

                                    <optgroup label="🧬 Diagnostic & Support Specialties">
                                        <option value="Pathology" {{ ($setting && $setting->specialty == 'Pathology') ? 'selected' : '' }}>Pathology (Laboratory medicine)</option>
                                        <option value="Radiology" {{ ($setting && $setting->specialty == 'Radiology') ? 'selected' : '' }}>Radiology (Medical imaging)</option>
                                        <option value="Interventional Radiology" {{ ($setting && $setting->specialty == 'Interventional Radiology') ? 'selected' : '' }}>Interventional Radiology</option>
                                        <option value="Nuclear Medicine" {{ ($setting && $setting->specialty == 'Nuclear Medicine') ? 'selected' : '' }}>Nuclear Medicine</option>
                                        <option value="Endoscopy" {{ ($setting && $setting->specialty == 'Endoscopy') ? 'selected' : '' }}>Endoscopy / GI Endoscopy</option>
                                        <option value="Electrodiagnostic Medicine" {{ ($setting && $setting->specialty == 'Electrodiagnostic Medicine') ? 'selected' : '' }}>Electrodiagnostic Medicine (EMG, EEG)</option>
                                    </optgroup>

                                    <optgroup label="🏥 Other Medical Specialties">
                                        <option value="Oncology" {{ ($setting && $setting->specialty == 'Oncology') ? 'selected' : '' }}>Oncology (Medical cancer care)</option>
                                        <option value="Hepatology" {{ ($setting && $setting->specialty == 'Hepatology') ? 'selected' : '' }}>Hepatology (Liver diseases)</option>
                                        <option value="Genetic Hematology" {{ ($setting && $setting->specialty == 'Genetic Hematology') ? 'selected' : '' }}>Genetic Hematology</option>
                                        <option value="Geriatrics" {{ ($setting && $setting->specialty == 'Geriatrics') ? 'selected' : '' }}>Geriatrics (Elderly care)</option>
                                        <option value="Physical Medicine & Rehabilitation" {{ ($setting && $setting->specialty == 'Physical Medicine & Rehabilitation') ? 'selected' : '' }}>Physical Medicine & Rehabilitation</option>
                                        <option value="Occupational & Environmental Medicine" {{ ($setting && $setting->specialty == 'Occupational & Environmental Medicine') ? 'selected' : '' }}>Occupational & Environmental Medicine</option>
                                        <option value="Sports Medicine" {{ ($setting && $setting->specialty == 'Sports Medicine') ? 'selected' : '' }}>Sports Medicine</option>
                                        <option value="Maternal Health Specialist" {{ ($setting && $setting->specialty == 'Maternal Health Specialist') ? 'selected' : '' }}>Maternal Health Specialist</option>
                                        <option value="Clinical Nutrition" {{ ($setting && $setting->specialty == 'Clinical Nutrition') ? 'selected' : '' }}>Clinical Nutrition / Dietetics</option>
                                        <option value="Neuro-rehabilitation" {{ ($setting && $setting->specialty == 'Neuro-rehabilitation') ? 'selected' : '' }}>Neuro-rehabilitation</option>
                                    </optgroup>

                                    <optgroup label="🧪 Specialized & Advanced Fields">
                                        <option value="Medical Genetics" {{ ($setting && $setting->specialty == 'Medical Genetics') ? 'selected' : '' }}>Medical Genetics</option>
                                        <option value="Hematologic Oncology" {{ ($setting && $setting->specialty == 'Hematologic Oncology') ? 'selected' : '' }}>Hematologic Oncology</option>
                                        <option value="Transplant Medicine" {{ ($setting && $setting->specialty == 'Transplant Medicine') ? 'selected' : '' }}>Transplant Medicine / Surgery</option>
                                        <option value="Tropical Medicine" {{ ($setting && $setting->specialty == 'Tropical Medicine') ? 'selected' : '' }}>Tropical Medicine</option>
                                        <option value="Pre-hospital Emergency" {{ ($setting && $setting->specialty == 'Pre-hospital Emergency') ? 'selected' : '' }}>Pre-hospital Emergency / EMS</option>
                                    </optgroup>

                                    <optgroup label="✏️ Custom">
                                        <option value="other">Other (Please specify)</option>
                                    </optgroup>
                                </select>

                                <!-- Custom Specialty Input (Hidden by default) -->
                                <div id="custom_specialty_container_settings" style="display: none;" class="mt-2">
                                    <input
                                        type="text"
                                        name="custom_specialty"
                                        id="custom_specialty_settings"
                                        class="form-select"
                                        placeholder="Please enter your medical specialty"
                                    >
                                </div>

                                <!-- Hidden field to store the final specialty value -->
                                <input type="hidden" name="specialty" id="specialty_settings" value="{{ $setting ? $setting->specialty : '' }}">
                            </div>

                            <h5 class="mb-4 mt-5 text-center">Notification Settings</h5>

                        <div class="mb-3">
                            <label for="notification_volume" class="form-label fw-semibold">Notification Volume</label>
                            <div class="d-flex align-items-center volume-controls">
                                <i class="bi bi-volume-mute me-2 text-muted"></i>
                                <input type="range" class="form-range flex-grow-1" id="notification_volume" name="notification_volume"
                                       min="0" max="1" step="0.1"
                                       value="{{ $setting ? $setting->notification_volume : 0.3 }}"
                                       aria-label="Notification volume control">
                                <i class="bi bi-volume-up ms-2 text-muted"></i>
                                <span class="ms-2 text-muted small" id="volume_value">
                                    {{ $setting ? round($setting->notification_volume * 100) : 30 }}%
                                </span>
                            </div>
                            <div class="form-text">
                                Adjust the volume level for notification sounds (0% = muted, 100% = full volume)
                            </div>
                        </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn" style="background-color: #DE6262; color: white;">
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>

<script>
function toggleCustomSpecialtySettings() {
    const select = document.getElementById('specialty_select');
    const customContainer = document.getElementById('custom_specialty_container_settings');
    const customInput = document.getElementById('custom_specialty_settings');
    const hiddenInput = document.getElementById('specialty_settings');

    if (select.value === 'other') {
        customContainer.style.display = 'block';
        customInput.required = true;
        customInput.focus();
        hiddenInput.value = ''; // Clear hidden field when showing custom input
    } else {
        customContainer.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
        hiddenInput.value = select.value; // Set hidden field to selected value
    }
}

// Initialize settings page functionality
document.addEventListener('DOMContentLoaded', function() {
    const customInput = document.getElementById('custom_specialty_settings');
    const hiddenInput = document.getElementById('specialty_settings');
    const select = document.getElementById('specialty_select');

    // Handle custom input changes
    customInput.addEventListener('input', function() {
        if (select.value === 'other') {
            hiddenInput.value = this.value;
        }
    });

    // Handle form submission
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const select = document.getElementById('specialty_select');
        const customInput = document.getElementById('custom_specialty_settings');
        const hiddenInput = document.getElementById('specialty_settings');

        if (select.value === 'other') {
            if (!customInput.value.trim()) {
                e.preventDefault();
                customInput.focus();
                customInput.style.borderColor = '#dc3545';
                return false;
            }
            hiddenInput.value = customInput.value.trim();
        } else {
            hiddenInput.value = select.value;
        }
    });

    // Initialize on page load - check if current specialty exists in dropdown
    const currentSpecialty = '{{ $setting ? $setting->specialty : "" }}';

    if (currentSpecialty) {
        // Check if current specialty exists in dropdown options
        const selectOptions = Array.from(document.getElementById('specialty_select').options);
        const optionExists = selectOptions.some(option => option.value === currentSpecialty);

        if (optionExists) {
            document.getElementById('specialty_select').value = currentSpecialty;
        } else {
            // If specialty doesn't exist in dropdown, treat as custom
            document.getElementById('specialty_select').value = 'other';
            toggleCustomSpecialtySettings();
            document.getElementById('custom_specialty_settings').value = currentSpecialty;
        }
        document.getElementById('specialty_settings').value = currentSpecialty;
    }

    // Volume slider functionality
    const volumeSlider = document.getElementById('notification_volume');
    const volumeValue = document.getElementById('volume_value');

    if (volumeSlider && volumeValue) {
        // Update volume display in real-time
        volumeSlider.addEventListener('input', function() {
            const percentage = Math.round(this.value * 100);
            volumeValue.textContent = percentage + '%';

            // Update visual feedback
            updateVolumeVisualFeedback(this.value);
        });

        // Handle keyboard navigation
        volumeSlider.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
                e.preventDefault();
                const newValue = Math.max(0, parseFloat(this.value) - 0.1);
                this.value = newValue;
                this.dispatchEvent(new Event('input'));
            } else if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
                e.preventDefault();
                const newValue = Math.min(1, parseFloat(this.value) + 0.1);
                this.value = newValue;
                this.dispatchEvent(new Event('input'));
            }
        });

        // Set initial volume display
        const initialVolume = parseFloat(volumeSlider.value);
        volumeValue.textContent = Math.round(initialVolume * 100) + '%';
        updateVolumeVisualFeedback(initialVolume);
    }

    function updateVolumeVisualFeedback(volume) {
        const volumeIcons = document.querySelectorAll('.volume-controls i');
        const muteIcon = volumeIcons[0];
        const upIcon = volumeIcons[1];

        if (volume <= 0) {
            muteIcon.className = 'bi bi-volume-mute text-danger';
            upIcon.className = 'bi bi-volume-up text-muted';
        } else if (volume < 0.5) {
            muteIcon.className = 'bi bi-volume-down text-warning';
            upIcon.className = 'bi bi-volume-up text-muted';
        } else {
            muteIcon.className = 'bi bi-volume-down text-success';
            upIcon.className = 'bi bi-volume-up text-success';
        }
    }
});
</script>

<style>
/* Custom specialty input styling for settings page */
#custom_specialty_container_settings {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#custom_specialty_settings {
    border: 2px solid #e9ecef;
    transition: border-color 0.3s ease;
}

#custom_specialty_settings:focus {
    border-color: #DE6262;
    box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
}

/* Volume slider cross-browser compatibility */
#notification_volume {
    -webkit-appearance: none;
    appearance: none;
    height: 6px;
    background: #ddd;
    border-radius: 3px;
    outline: none;
    transition: background 0.3s ease;
}

#notification_volume::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    background: #DE6262;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

#notification_volume::-webkit-slider-thumb:hover {
    background: #c85151;
    transform: scale(1.1);
}

#notification_volume::-moz-range-thumb {
    width: 20px;
    height: 20px;
    background: #DE6262;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

#notification_volume::-moz-range-thumb:hover {
    background: #c85151;
    transform: scale(1.1);
}

#notification_volume::-ms-thumb {
    width: 20px;
    height: 20px;
    background: #DE6262;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

#notification_volume::-ms-thumb:hover {
    background: #c85151;
}

#notification_volume:focus::-webkit-slider-thumb {
    box-shadow: 0 0 0 3px rgba(222, 98, 98, 0.3);
}

#notification_volume:focus::-moz-range-thumb {
    box-shadow: 0 0 0 3px rgba(222, 98, 98, 0.3);
}

#notification_volume:focus::-ms-thumb {
    box-shadow: 0 0 0 3px rgba(222, 98, 98, 0.3);
}

/* Volume icons styling */
.volume-controls i {
    color: #6c757d;
    transition: color 0.2s ease;
}

.volume-controls i:first-child {
    opacity: 0.7;
}

.volume-controls i:last-child {
    opacity: 0.7;
}
</style>

@endsection
