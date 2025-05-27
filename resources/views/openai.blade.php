<!-- resources/views/openai-form.blade.php -->
@extends('master')

@section('title', 'Patients Page')

@section('content')

<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<!-- Include CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

<!-- Include JS -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

        <div class="container medical-form-container ">
            <form id="openaiForm" action="{{ url('/openai/respond') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="medical-form-card">
        
                    <div id="errorMessages"></div>
        <!-- Patient Info --><br>
                    <div class="medical-form-section">
                        <h4>Patient Information</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="name" class="form-label required">Name:</label>
                                <input type="text" id="name" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label for="age" class="form-label required">Age:</label>
                                <input type="number" id="age" name="age" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label for="gender" class="form-label required">Gender:</label>
                                <select name="gender" id="gender" class="form-select">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Upload Medical Reports:</label>
                                <input type="file" id="reports" name="reports[]" multiple class="form-control">
                                <div id="upload-status" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
        
                    <!-- Vitals --><br>
                    <div class="medical-form-section">
                        <h4>Physical Attributes / Vitals</h4>
                        <div class="row">
                            <div class="col-md-2">
                                <label class="form-label">Weight (kg):</label>
                                <input type="text" name="weight" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Height (cm):</label>
                                <input type="text" name="height" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Temperature (°C):</label>
                                <input type="text" name="temperature" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Blood Pressure:</label>
                                <input type="text" name="blood_pressure" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Blood Sugar:</label>
                                <input type="text" name="blood_sugar" class="form-control">
                            </div>
                        </div>
                    </div>
        
                    <!-- Symptoms --><br>
                    <div class="medical-form-section">
                        <h4>Symptoms</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Current Symptoms:</label>
                                <select id="current_symptoms" name="current_symptoms[]" multiple>
                                    @foreach($symptoms as $symptom)
                                        <option value="{{ $symptom->id }}">{{ $symptom->name }}</option>
                                    @endforeach
                                </select>
                                
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Select Common Symptoms:</label>
                                <div class="form-check">
                                    <input type="checkbox" name="symptoms_checkboxes[]" value="fever" class="form-check-input" id="fever">
                                    <label class="form-check-label" for="fever">Fever</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="symptoms_checkboxes[]" value="cough" class="form-check-input" id="cough">
                                    <label class="form-check-label" for="cough">Cough</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="symptoms_checkboxes[]" value="headache" class="form-check-input" id="headache">
                                    <label class="form-check-label" for="headache">Headache</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="symptoms_checkboxes[]" value="fatigue" class="form-check-input" id="fatigue">
                                    <label class="form-check-label" for="fatigue">Fatigue</label>
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <!-- Tests and Diagnosis --><br>
                    <div class="medical-form-section">
                        <h4>Test Results & Preliminary Diagnosis</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Test Results:</label>
                                <textarea name="test_results" class="form-control" placeholder="e.g., CRP: Elevated at 15 mg/L."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preliminary Diagnosis:</label>
                                <textarea name="preliminary_diagnosis" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
        
                <!-- Submit -->
                <div class="row mt-4">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-deep-red btn-lg px-4 ">Get Results</button>
                    </div>
                </div>


        
                </div>
            </form>
        </div>

        <div id="page-loader" style="display:none;">
            <div id='css3-spinner-svg-pulse-wrapper'>
                <svg id='css3-spinner-svg-pulse' version='1.2' height='210' width='550'
                     xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'>
                    <path id='css3-spinner-pulse' stroke='#DE6262' fill='none' stroke-width='2'
                          stroke-linejoin='round'
                          d='M0,90L250,90Q257,60 262,87T267,95 270,88 273,92t6,35 7,-60T290,127 297,107s2,-11 10,-10 1,1 8,-10T319,95c6,4 8,-6 10,-17s2,10 9,11h210'></path>
                </svg>
            </div>
        </div>
        
        
<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content response-modal-content">
            <div class="modal-header response-modal-header">
                <h5 class="modal-title" id="responseModalLabel" style="color: aliceblue">OpenAI Response</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body response-modal-body">
                <div class="response-block">
                    <pre id="openaiReply" class="response-text"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

  
  
  

        

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const element = document.getElementById('current_symptoms');
        new Choices(element, {
            removeItemButton: true,
            placeholderValue: 'Select symptoms...',
            searchPlaceholderValue: 'Search...',
            classNames: {
                containerInner: 'form-control',
                 // Applies your custom styles
            }
        });
    });
</script>

</script>


<script>
    document.getElementById('openaiForm').addEventListener('submit', function () {
        document.getElementById('page-loader').style.display = 'block';
    });

</script>


@if (session('openai_result'))
    <script>
         document.addEventListener('DOMContentLoaded', function () {
            // Set the response content in the modal
            document.getElementById('openaiReply').textContent = @json(session('openai_result'));

            // You can set other session data in the modal if needed
            // Example: document.getElementById('userPrompt').textContent = @json(session('openai_prompt'));

            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('responseModal'));
            modal.show();

            // Hide the page loader once the modal is shown
            document.getElementById('page-loader').style.display = 'none';
        });
    </script>
@endif





    @endsection