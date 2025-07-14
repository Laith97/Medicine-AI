<?php

namespace App\Http\Controllers;

use App\Models\PatientAnalysis;
use App\Models\Symptom;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIController extends Controller

{
    public function showForm(Request $request)
    {
        $symptoms = Symptom::all();
        
        // Check if we're editing an existing patient
        $patientToEdit = null;
        if ($request->has('edit_patient')) {
            $patientToEdit = PatientAnalysis::where('id', $request->edit_patient)
                ->where('user_id', auth()->id())
                ->first();
        }
        
        // Get all patient records for the current user
        $allPatientRecords = PatientAnalysis::where('user_id', auth()->id())
            ->select('id', 'name', 'age', 'gender', 'created_at', 'patient_key', 'visit_number', 'weight', 'height', 'symptoms', 'test_results')
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Group patients by patient_key to count visits and get the most recent record
        $patientGroups = [];
        $patientVisits = [];
        
        foreach ($allPatientRecords as $record) {
            // If patient_key is not set, use the name-age-gender combination
            $key = $record->patient_key ?? ($record->name . '-' . $record->age . '-' . $record->gender);
            
            if (!isset($patientGroups[$key])) {
                $patientGroups[$key] = $record; // Store the most recent record (first one due to ordering)
                $patientVisits[$key] = ['count' => 0, 'patient' => $record];
            }
            
            $patientVisits[$key]['count']++;
        }
        
        // Convert the grouped patients back to a collection
        $existingPatients = collect(array_values($patientGroups));
        
        // Create a simplified version of patientVisits for JavaScript
        // We'll use both patient_key and name-age-gender as keys to ensure compatibility
        $simplifiedVisits = [];
        foreach ($existingPatients as $patient) {
            $nameAgeGenderKey = $patient->name . '-' . $patient->age . '-' . $patient->gender;
            $patientKey = $patient->patient_key ?? $nameAgeGenderKey;
            
            if (isset($patientVisits[$patientKey])) {
                // Add entry with patient_key if it exists
                if ($patient->patient_key) {
                    $simplifiedVisits[$patient->patient_key] = $patientVisits[$patientKey];
                }
                
                // Also add entry with name-age-gender key for backward compatibility
                $simplifiedVisits[$nameAgeGenderKey] = $patientVisits[$patientKey];
            } else {
                // Fallback if the key doesn't exist
                $simplifiedVisits[$nameAgeGenderKey] = ['count' => 1, 'patient' => $patient];
                if ($patient->patient_key) {
                    $simplifiedVisits[$patient->patient_key] = ['count' => 1, 'patient' => $patient];
                }
            }
        }

        // Pass both simplifiedVisits and patientVisits to the view for backward compatibility
        return view('openai', compact('symptoms', 'existingPatients', 'simplifiedVisits', 'patientVisits', 'patientToEdit'));
    }

    
    public function getResponse(Request $request)
    {
        try {
            $files = $request->file('reports');
        
            $uploadedFileIds = [];
            $imageMessages = [];
        
            $inputData = $this->collectPatientData($request);
            $criterion = auth()->user()->setting->criterion ?? 'CDC';
            
            if ($files && is_array($files)) {
                foreach ($files as $file) {
                    $originalName = $file->getClientOriginalName();
                    $extension = strtolower($file->getClientOriginalExtension());
                    $tempPath = storage_path('app/tmp/' . $originalName);
        
                    // Move file to temp path
                    $file->move(storage_path('app/tmp'), $originalName);
        
                    // Check if it's an image file
                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                        // Process as image for GPT-4 Vision
                        $base64 = base64_encode(file_get_contents($tempPath));
                        $mimeType = mime_content_type($tempPath);
        
                        $imageMessages[] = [
                            "type" => "image_url",
                            "image_url" => [
                                "url" => "data:$mimeType;base64,$base64"
                            ]
                        ];
                    } else {
                        try {
                            // Try to upload any other file type for file_search
                            $uploaded = OpenAI::files()->upload([
                                'purpose' => 'assistants',
                                'file' => fopen($tempPath, 'r'),
                            ]);
            
                            $uploadedFileIds[] = $uploaded['id'];
                        } catch (\Exception $e) {
                            // If the file type is not supported by OpenAI, log the error
                            \Log::warning("File type not supported by OpenAI: {$originalName}. Error: {$e->getMessage()}");
                            
                            // For unsupported file types, we'll try to extract text if possible
                            $fileContent = $this->tryExtractTextFromFile($tempPath, $extension);
                            
                            if (!empty($fileContent)) {
                                // Log the successful extraction
                                \Log::info("Successfully extracted text from file: {$originalName}. Length: " . strlen($fileContent));
                                
                                // Create a temporary text file with the extracted content
                                $textFilePath = storage_path('app/tmp/extracted_' . pathinfo($originalName, PATHINFO_FILENAME) . '.txt');
                                file_put_contents($textFilePath, $fileContent);
                                
                                try {
                                    // Upload the text file instead
                                    $uploaded = OpenAI::files()->upload([
                                        'purpose' => 'assistants',
                                        'file' => fopen($textFilePath, 'r'),
                                    ]);
                                    
                                    $uploadedFileIds[] = $uploaded['id'];
                                    \Log::info("Successfully uploaded extracted text as file: {$uploaded['id']}");
                                } catch (\Exception $e2) {
                                    \Log::error("Failed to upload extracted text file: {$e2->getMessage()}");
                                    
                                    // If we can't upload the file, add the content directly to the prompt
                                    // This is a fallback for when file upload fails
                                    $fileExtractedContent = "Content from {$originalName}:\n\n" . $fileContent;
                                    
                                    // Add the extracted content to the image messages if it's not too long
                                    if (strlen($fileExtractedContent) < 4000) {
                                        $imageMessages[] = [
                                            "type" => "text",
                                            "text" => $fileExtractedContent
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        
            // GPT-4 Vision
            if (!empty($imageMessages)) {
                // Create a system message that instructs the model to analyze all images
                $specialty = auth()->user()->setting->specialty ?? 'medicine';
                $systemMessage = "You are a senior consultant physician specialized in {$specialty} with 20+ years of clinical experience. 
                    You have extensive training in analyzing medical images and documents.
                    
                    CRITICAL CLINICAL APPROACH:
                    1. ALWAYS prioritize life-threatening conditions first in your differential diagnosis
                    2. Assign specific probability percentages to each diagnosis (e.g., 70%, 25%, 5%)
                    3. Provide clear clinical reasoning for each diagnosis
                    4. Use proper medical terminology throughout your assessment
                    5. Be specific and detailed in your recommendations
                    6. NEVER be vague or overly reassuring about serious symptoms
                    7. Flag cases as ROUTINE, URGENT, or EMERGENCY based on clinical presentation
                    8. Include specific medication recommendations when appropriate
                    9. Recommend specialist referrals when indicated
                    
                    IMPORTANT INSTRUCTIONS:
                    1. Begin your response with a 'CASE URGENCY' classification (ROUTINE/URGENT/EMERGENCY)
                    
                    2. Then provide a 'PATIENT INFORMATION' section that includes:
                       - Basic patient details from the input data
                       - A subsection called 'MEDICAL REPORTS ANALYSIS' with a concise summary of all uploaded images
                    
                    3. For each image in the MEDICAL REPORTS ANALYSIS:
                       - Identify and describe what the image shows (brain scan, x-ray, ultrasound, etc.)
                       - Identify any visible abnormalities, lesions, or notable findings
                       - Extract any text visible in the images (lab values, measurements, annotations)
                       - Keep descriptions concise even if there are many images
                    
                    4. After the PATIENT INFORMATION section, proceed with your diagnosis sections (A, B, C, D)
                    
                    DO NOT say you cannot analyze the image. If you can see the image at all, provide your best medical analysis.
                    If the image is unclear, still describe what you can see and provide possible interpretations.";
                
                $response = OpenAI::chat()->create([
                   'model' => 'gpt-4o',
    
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemMessage
                        ],
                        [
                            'role' => 'user',
                            'content' => array_merge(
                                [['type' => 'text', 'text' => "I'm uploading " . count($imageMessages) . " medical image(s) for analysis. Please analyze these images thoroughly from an " . $specialty . " perspective. " . $this->preparePrompt($inputData, $criterion, false)]],
                                $imageMessages
                            )
                        ]
                    ]
                ]);
                
                $rawMessage = $response['choices'][0]['message']['content'] ?? '';
    
                $filteredMessage = $this->filterReponse($rawMessage);
                
                $this->insertTotable($request,$filteredMessage);
                return redirect()->back()->with([
                    'openai_result' => $filteredMessage,
                ]);
                
            }
        
            // File Search
            if (!empty($uploadedFileIds)) {
                $vectorStore = OpenAI::vectorStores()->create([
                    'file_ids' => $uploadedFileIds,
                ]);
                $vectorStoreId = $vectorStore['id'];
        
                $specialty = auth()->user()->setting->specialty ?? 'Internal Medicine';
                
                $assistant = OpenAI::assistants()->create([
                    'name' => 'Medical Document Analyzer',
                    'instructions' => "You are a senior consultant physician specialized in {$specialty} with 20+ years of clinical experience. Your task is to thoroughly analyze ALL uploaded medical documents to extract relevant clinical information.
                    
                    CRITICAL CLINICAL APPROACH:
                    1. ALWAYS prioritize life-threatening conditions first in your differential diagnosis
                    2. Assign specific probability percentages to each diagnosis (e.g., 70%, 25%, 5%)
                    3. Provide clear clinical reasoning for each diagnosis
                    4. Use proper medical terminology throughout your assessment
                    5. Be specific and detailed in your recommendations
                    6. NEVER be vague or overly reassuring about serious symptoms
                    7. Flag cases as ROUTINE, URGENT, or EMERGENCY based on clinical presentation
                    8. Include specific medication recommendations when appropriate
                    9. Recommend specialist referrals when indicated
                    
                    IMPORTANT INSTRUCTIONS:
                    1. Begin your response with a 'CASE URGENCY' classification (ROUTINE/URGENT/EMERGENCY)
                    
                    2. Then provide a 'PATIENT INFORMATION' section that includes:
                       - Basic patient details from the input data
                       - Vital signs assessment (if provided)
                       - A subsection called 'MEDICAL REPORTS ANALYSIS' with a concise summary of all uploaded files
                    
                    3. For the MEDICAL REPORTS ANALYSIS:
                       - Keep it concise even if there are many files (10+)
                       - Group similar files together and summarize key findings
                       - For images: Brief description and key findings
                       - For text documents: Key medical information and relevance
                    
                    4. Examine EACH file thoroughly and extract ALL medical information:
                       - For medical documents: Extract symptoms, diagnoses, test results, and treatments
                       - For text documents about medical conditions: Summarize key information and connect to the patient's case
                       - For images: Describe what they show and identify any abnormalities
                    
                    5. After the PATIENT INFORMATION section, proceed with your diagnosis sections (A, B, C, D)
                    
                    DO NOT say you cannot analyze the files. If you can access the file content at all, provide your best medical analysis based on what you can see.",
                    'tools' => [['type' => 'file_search']],
                    'tool_resources' => [
                        'file_search' => [
                            'vector_store_ids' => [$vectorStoreId],
                        ],
                    ],
                    'model' => 'gpt-4o',
                ]);
        
                $thread = OpenAI::threads()->create([]);
                $threadId = $thread['id'];
                
                // Store thread ID in session for follow-up messages
                session(['thread_id' => $threadId]);
                
                // Create a conversation ID for follow-up messages
                $conversationId = uniqid('conv_');
                session(['conversation_id' => $conversationId]);
                
                // Store the initial conversation in the session
                $specialty = auth()->user()->setting->specialty ?? null;
                $initialPrompt = $this->preparePrompt($inputData, $criterion, true);
                session(['conversation_history_' . $conversationId => [
                    ['role' => 'system', 'content' => $this->getSystemPrompt($specialty, $criterion)],
                    ['role' => 'user', 'content' => $initialPrompt]
                ]]);
        
                // Create a more detailed prompt that specifically mentions the files
                $fileNames = [];
                foreach ($files as $file) {
                    $fileNames[] = $file->getClientOriginalName();
                }
                
                $fileListText = "I've uploaded the following files for analysis:\n";
                foreach ($fileNames as $index => $name) {
                    $fileListText .= ($index + 1) . ". " . $name . "\n";
                }
                
                $enhancedPrompt = $fileListText . "\n" . $initialPrompt . "\n\nPlease analyze ALL these files thoroughly from your {$specialty} perspective. Don't skip any files, and make sure to extract all relevant medical information.";
                
                OpenAI::threads()->messages()->create($threadId, [
                    'role' => 'user',
                    'content' => $enhancedPrompt,
                ]);
        
                $run = OpenAI::threads()->runs()->create($threadId, [
                    'assistant_id' => $assistant['id'],
                ]);
                $runId = $run['id'];
                
    
                return $this->checkRunStatus($request, $threadId, $runId);
            }
        
            // No files provided: still try to respond based on inputData alone
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->preparePrompt($inputData, $criterion, false),
                    ]
                ]
            ]);
            $rawMessage = $response['choices'][0]['message']['content'] ?? '';
            
            $filteredMessage = $this->filterReponse($rawMessage);
            // ✅ Save to database
            $this->insertTotable($request, $filteredMessage);
            
            // Create a conversation ID for follow-up messages
            $conversationId = uniqid('conv_');
            session(['conversation_id' => $conversationId]);
            
            // Store the initial conversation in the session
            $specialty = auth()->user()->setting->specialty ?? null;
            session(['conversation_history_' . $conversationId => [
                ['role' => 'system', 'content' => $this->getSystemPrompt($specialty, $criterion)],
                ['role' => 'user', 'content' => $this->preparePrompt($inputData, $criterion, false)],
                ['role' => 'assistant', 'content' => $rawMessage]
            ]]);
            
            return redirect()->back()->with([
                'openai_result' => $filteredMessage,
                'conversation_id' => $conversationId,
            ]);
        } catch (\Exception $e) {
            // Check if it's an API key issue
            $message = $e->getMessage();
            if (strpos($message, 'API key') !== false || 
                strpos($message, 'authentication') !== false || 
                strpos($message, '401') !== false || 
                strpos($message, 'Unauthorized') !== false) {
                
                // It's likely an API key issue
                return redirect()->back()->with([
                    'openai_api_error' => 'Your OpenAI API key appears to be invalid or expired. Please contact the administrator to update the API key.',
                ]);
            }
            
            // For other errors
            return redirect()->back()->with([
                'openai_error' => 'An error occurred while processing your request: ' . $e->getMessage(),
            ]);
        }
    }

    
    
    
    public function checkRunStatus($request, $threadId, $runId)
    {
        $maxAttempts = 30; // Increase max attempts
        $delayMicroseconds = 1000000; // 1 second
        
        \Log::info("Checking run status for thread: $threadId, run: $runId");
    
        for ($i = 0; $i < $maxAttempts; $i++) {
            $runStatus = OpenAI::threads()->runs()->retrieve($threadId, $runId);
            \Log::info("Run status: " . $runStatus['status'] . " (attempt $i)");

            if ($runStatus['status'] === 'completed') {
                // Get all messages from the thread
                $messages = OpenAI::threads()->messages()->list($threadId, [
                    'limit' => 10, // Get more messages to ensure we have the complete response
                    'order' => 'desc' // Get newest first
                ]);
                
                // Log the number of messages received
                \Log::info("Retrieved " . count($messages['data']) . " messages from thread");
                
                // Get the assistant's response (should be the first message)
                $assistantMessage = null;
                foreach ($messages['data'] as $message) {
                    if ($message['role'] === 'assistant') {
                        $assistantMessage = $message;
                        break;
                    }
                }
                
                if (!$assistantMessage) {
                    \Log::error("No assistant message found in thread: $threadId");
                    return redirect()->back()->with([
                        'openai_error' => 'No response was generated. Please try again.',
                    ]);
                }
                
                // Combine all content parts (text, images, etc.)
                $fullContent = '';
                foreach ($assistantMessage['content'] as $contentPart) {
                    if ($contentPart['type'] === 'text') {
                        $fullContent .= $contentPart['text']['value'] . "\n\n";
                    }
                }
                
                $lastMessage = trim($fullContent);
                \Log::info("Response length: " . strlen($lastMessage) . " characters");
                
                if (empty($lastMessage)) {
                    \Log::error("Empty response from assistant in thread: $threadId");
                    return redirect()->back()->with([
                        'openai_error' => 'The response was empty. Please try again.',
                    ]);
                }
                
                $lastMessage = $this->filterReponse($lastMessage);
                
                // Save to database
                $this->insertTotable($request, $lastMessage);
                
                // Get the conversation ID from session
                $conversationId = session('conversation_id');
                
                // Add the AI's response to the conversation history
                if ($conversationId) {
                    $conversationHistory = session('conversation_history_' . $conversationId, []);
                    if (!empty($conversationHistory)) {
                        $conversationHistory[] = ['role' => 'assistant', 'content' => $lastMessage];
                        session(['conversation_history_' . $conversationId => $conversationHistory]);
                    }
                }
                
                return redirect()->back()->with([
                    'openai_result' => $lastMessage,
                    'conversation_id' => $conversationId,
                ]);
            } else if ($runStatus['status'] === 'failed') {
                \Log::error("Run failed: " . json_encode($runStatus));
                return redirect()->back()->with([
                    'openai_error' => 'The analysis failed. Error: ' . ($runStatus['last_error']['message'] ?? 'Unknown error'),
                ]);
            } else if ($runStatus['status'] === 'requires_action') {
                \Log::info("Run requires action: " . json_encode($runStatus['required_action']));
                // Handle required actions (like function calls)
                // For now, we'll just continue waiting
            }
    
            usleep($delayMicroseconds);
        }
    
        \Log::warning("Run timed out after $maxAttempts attempts");
        return redirect()->back()->with([
            'openai_error' => 'The analysis is taking longer than expected. Please check back in a few minutes.',
        ]);
    }
    
    

    private function collectPatientData(Request $request)
    {
        // Check if we're using an existing patient
        if ($request->patient_selection && $request->patient_selection != 'new') {
            // Get the existing patient data
            $existingPatient = PatientAnalysis::find($request->patient_selection);
            
            if ($existingPatient) {
                // Get the patient's history using patient_key if available
                $patientHistory = $existingPatient->getPatientHistory();
                
                // Get the latest record for this patient
                $latestRecord = $patientHistory->sortByDesc('visit_number')->first();
                
                // Use the latest record if available, otherwise use the selected one
                $patientRecord = $latestRecord ?? $existingPatient;
                
                // Count the number of visits
                $visitCount = $patientHistory->count();
                
                // Calculate the visit number for this specific visit
                // This will be the next visit number (count + 1) since we're creating a new record
                $currentVisitNumber = $visitCount + 1;
                
                // Get previous medical history from past visits
                $previousMedicalHistory = '';
                if ($visitCount > 0) {
                    $previousMedicalHistory = "PATIENT HISTORY SUMMARY:\n";
                    $previousMedicalHistory .= "Total previous visits: " . $visitCount . "\n\n";
                    
                    // Get the previous visits with more detailed information
                    $previousVisits = $patientHistory->sortByDesc('created_at')->take(3)->map(function($record, $index) {
                        $date = $record->created_at->format('M d, Y');
                        $visitNum = $record->visit_number ?? ($index + 1);
                        
                        $visitSummary = "VISIT #$visitNum ($date):\n";
                        
                        // Add vital signs if available
                        $vitalSigns = [];
                        if ($record->temperature) $vitalSigns[] = "Temperature: " . $record->temperature;
                        if ($record->blood_pressure) $vitalSigns[] = "BP: " . $record->blood_pressure;
                        if ($record->blood_sugar) $vitalSigns[] = "Blood Sugar: " . $record->blood_sugar;
                        if ($record->weight) $vitalSigns[] = "Weight: " . $record->weight;
                        if ($record->height) $vitalSigns[] = "Height: " . $record->height;
                        
                        if (!empty($vitalSigns)) {
                            $visitSummary .= "Vitals: " . implode(", ", $vitalSigns) . "\n";
                        }
                        
                        // Add symptoms
                        if ($record->symptoms) {
                            $symptoms = is_string($record->symptoms) ? json_decode($record->symptoms, true) : $record->symptoms;
                            
                            // If symptoms are numeric IDs, try to get the actual symptom names
                            if (is_array($symptoms) && !empty($symptoms) && is_numeric($symptoms[0])) {
                                $processedSymptoms = $this->processSymptoms($symptoms);
                                $symptomsText = implode(", ", $processedSymptoms);
                            } else {
                                $symptomsText = is_array($symptoms) ? implode(", ", $symptoms) : $symptoms;
                            }
                            
                            $visitSummary .= "Symptoms: $symptomsText\n";
                        }
                        
                        // Add test results if available
                        if ($record->test_results) {
                            $visitSummary .= "Test Results: " . $record->test_results . "\n";
                        }
                        
                        // Extract diagnoses from AI response
                        if ($record->ai_response) {
                            // Try to extract differential diagnosis
                            if (preg_match('/(?:A\)\s*POSSIBLE\s*DIAGNOSIS|A\)\s*DIFFERENTIAL\s*DIAGNOSIS)[\s\S]*?(?=B\)|$)/i', $record->ai_response, $matches)) {
                                $diagnosis = trim(strip_tags($matches[0]));
                                $visitSummary .= "Diagnosis: " . str_replace("\n", " ", $diagnosis) . "\n";
                            }
                            
                            // Try to extract treatment recommendations
                            if (preg_match('/(?:C\)\s*TREATMENT\s*RECOMMENDATIONS|C\)\s*MANAGEMENT\s*RECOMMENDATIONS)[\s\S]*?(?=D\)|$)/i', $record->ai_response, $matches)) {
                                $treatment = trim(strip_tags($matches[0]));
                                $visitSummary .= "Treatment: " . str_replace("\n", " ", $treatment) . "\n";
                            }
                        }
                        
                        return $visitSummary;
                    })->join("\n");
                    
                    $previousMedicalHistory .= $previousVisits;
                    
                    // Add note about clinical progression
                    $previousMedicalHistory .= "\nIMPORTANT: Consider the clinical progression across these visits when formulating your assessment.";
                }
                
                return [
                    'name' => $patientRecord->name,
                    'age' => $patientRecord->age,
                    'gender' => $patientRecord->gender,
                    'weight' => $request->weight ?: $patientRecord->weight,
                    'height' => $request->height ?: $patientRecord->height,
                    'symptoms' => $this->processSymptoms($request->current_symptoms, $request->custom_symptoms),
                    'test_results' => $request->test_results,
                    'clinical_status' => [
                        'temperature' => is_numeric($request->temperature) ? $request->temperature : null,
                        'blood_pressure' => $request->blood_pressure,
                        'blood_sugar' => is_numeric($request->blood_sugar) ? $request->blood_sugar : null,
                    ],
                    'reports' => $request->file('reports') ?? null,
                    'preliminary_diagnosis' => $request->preliminary_diagnosis,
                    'is_existing_patient' => true,
                    'patient_id' => $patientRecord->id,
                    'previous_record_id' => $patientRecord->id, // Store the previous record ID for reference
                    'visit_count' => $visitCount,
                    'current_visit_number' => $currentVisitNumber,
                    'previous_medical_history' => $previousMedicalHistory
                ];
            }
        }
        
        // New patient or existing patient not found
        return [
            'name' => $request->name,
            'age' => $request->age,
            'gender' => $request->gender,
            'weight' => $request->weight,
            'height' => $request->height,
            'symptoms' => $this->processSymptoms($request->current_symptoms, $request->custom_symptoms),
            'test_results' => $request->test_results,
            'clinical_status' => [
                'temperature' => is_numeric($request->temperature) ? $request->temperature : null,
                'blood_pressure' => $request->blood_pressure,
                'blood_sugar' => is_numeric($request->blood_sugar) ? $request->blood_sugar : null,
            ],
            'reports' => $request->file('reports') ?? null,
            'preliminary_diagnosis' => $request->preliminary_diagnosis,
            'is_existing_patient' => false
        ];
    }

    /**
     * Remove Patient Information section from the AI response
     */
    private function removePatientInfoSection($text)
    {
        // Skip this function if the text contains our new PATIENT INFORMATION format
        if (preg_match('/PATIENT\s+INFORMATION:[\s\S]*?MEDICAL\s+REPORTS\s+ANALYSIS/i', $text)) {
            return $text;
        }
        
        // Check if the text contains an old Patient Information section
        if (preg_match('/Patient Information:[\s\S]*?---/i', $text, $matches)) {
            // Remove the entire section including the separator line
            $text = str_replace($matches[0], '', $text);
            
            // Clean up any extra newlines that might be left
            $text = preg_replace("/\n{3,}/", "\n\n", $text);
        }
        
        // Also check for the specific format with Age, Gender, Total Visits
        if (preg_match('/Age:\s*\d+\s*\n+Gender:\s*[a-zA-Z]+\s*\n+Total Visits:\s*\d+/i', $text, $matches)) {
            // Remove this section as well
            $text = str_replace($matches[0], '', $text);
            
            // Clean up any extra newlines that might be left
            $text = preg_replace("/\n{3,}/", "\n\n", $text);
        }
        
        return $text;
    }
    
    private function filterReponse($lastMessage)
    {
        // Extract the PATIENT INFORMATION section with MEDICAL REPORTS ANALYSIS
        $patientInfoPattern = '/PATIENT\s+INFORMATION:[\s\S]*?(?=A\)\s*POSSIBLE\s*DIAGNOSIS:|A\)\s*DIFFERENTIAL\s*DIAGNOSIS)/i';
        $patientInfoContent = '';
        
        if (preg_match($patientInfoPattern, $lastMessage, $matches)) {
            $patientInfoContent = $matches[0];
            // Don't remove it yet, we'll add it back later
        }
        
        // Extract the CASE URGENCY section if it exists
        $urgencyPattern = '/CASE\s+URGENCY:\s*(ROUTINE|URGENT|EMERGENCY).*?(?=PATIENT\s+INFORMATION:|$)/i';
        $urgencyContent = '';
        
        if (preg_match($urgencyPattern, $lastMessage, $matches)) {
            $urgencyContent = $matches[0];
        }
        
        // Remove markdown bold and italic (**bold**, *italic*)
        $lastMessage = preg_replace('/\*\*(.*?)\*\*/', '$1', $lastMessage);
        $lastMessage = preg_replace('/\*(.*?)\*/', '$1', $lastMessage);
    
        // Remove markdown headers (##, ###, etc.)
        $lastMessage = preg_replace('/#+\s*/', '', $lastMessage);
    
        // Remove bullet points (-, *, •)
        $lastMessage = preg_replace('/^[\-\*\•]\s+/m', '', $lastMessage);
    
        // Remove extra whitespace at beginning of lines
        $lastMessage = preg_replace('/^\s+/m', '', $lastMessage);
    
        // Normalize multiple newlines to a single one
        $lastMessage = preg_replace("/\n{2,}/", "\n\n", $lastMessage);
    
        // Decode HTML entities and strip HTML tags
        $lastMessage = html_entity_decode($lastMessage, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lastMessage = strip_tags($lastMessage);
    
        // Enhance section headers like A), B), etc.
        $lastMessage = preg_replace_callback('/^([A-D])\)\s*(.+)$/m', function ($matches) {
            return "\n\n" . strtoupper($matches[1] . ') ' . $matches[2]) . "\n";
        }, $lastMessage);
        
        // Check if there's a Current Symptoms section before saving it
        $currentSymptomsPattern = '/Current\s+Symptoms:.*?(?=A\)\s*POSSIBLE\s*DIAGNOSIS:|A\)\s*DIFFERENTIAL\s*DIAGNOSIS|$)/is';
        $currentSymptomsMatch = null;
        if (preg_match($currentSymptomsPattern, $lastMessage, $currentSymptomsMatch)) {
            $currentSymptoms = trim($currentSymptomsMatch[0]);
        }
        
        // Look for either the old or new diagnosis section header
        $diagnosisPattern = '/(A\)\s*POSSIBLE\s*DIAGNOSIS:|A\)\s*DIFFERENTIAL\s*DIAGNOSIS)/i';
        if (preg_match($diagnosisPattern, $lastMessage, $match, PREG_OFFSET_CAPTURE)) {
            $startPos = $match[0][1];
            $diagnosisPart = substr($lastMessage, $startPos);
            
            // Construct the final message with proper sections
            $finalMessage = '';
            
            // Add urgency section if we found it
            if (!empty($urgencyContent)) {
                $finalMessage .= trim($urgencyContent) . "\n\n";
            }
            
            // Add patient information section if we found it
            if (!empty($patientInfoContent)) {
                $finalMessage .= trim($patientInfoContent) . "\n\n";
            }
            
            // If we found Current Symptoms, add it after patient info
            if (!empty($currentSymptoms)) {
                $finalMessage .= trim($currentSymptoms) . "\n\n";
            }
            
            // Add the diagnosis part
            $finalMessage .= $diagnosisPart;
            
            $lastMessage = $finalMessage;
        }
    
        // Apply our structured formatting
        $lastMessage = $this->formatResponseStructure($lastMessage);
        
        // Final trim
        return trim($lastMessage);
    }
    /**
     * Try to extract text from various file types
     * 
     * @param string $filePath Path to the file
     * @param string $extension File extension
     * @return string Extracted text or empty string if extraction failed
     */
    private function tryExtractTextFromFile($filePath, $extension)
    {
        try {
            // For PDF files, use the PDF parser
            if (strtolower($extension) === 'pdf') {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                return $pdf->getText();
            }
            
            // For Office documents (.docx, .xlsx, .pptx)
            if (in_array(strtolower($extension), ['docx', 'xlsx', 'pptx'])) {
                // Try to use PhpOffice if available
                if (class_exists('\\PhpOffice\\PhpWord\\IOFactory') && $extension === 'docx') {
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
                    $text = '';
                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $text .= $element->getText() . "\n";
                            }
                        }
                    }
                    return $text;
                }
            }
            
            // For OpenDocument formats (.odt, .ods, .odp)
            if (in_array(strtolower($extension), ['odt', 'ods', 'odp'])) {
                // Try to extract as ZIP and read content.xml
                $zip = new \ZipArchive();
                if ($zip->open($filePath) === true) {
                    if (($index = $zip->locateName('content.xml')) !== false) {
                        $content = $zip->getFromIndex($index);
                        $zip->close();
                        
                        // Better XML extraction for OpenDocument
                        $text = '';
                        
                        // Remove XML namespaces to simplify parsing
                        $content = preg_replace('/<[a-z0-9]+:[^>]+>/i', '', $content);
                        $content = preg_replace('/<\/[a-z0-9]+:[^>]+>/i', '', $content);
                        
                        // Extract text content
                        if (preg_match_all('/<text:p[^>]*>(.*?)<\/text:p>/s', $content, $matches)) {
                            foreach ($matches[1] as $paragraph) {
                                $text .= strip_tags($paragraph) . "\n";
                            }
                        }
                        
                        // If the above didn't work, fall back to simple stripping
                        if (empty(trim($text))) {
                            $text = strip_tags($content);
                        }
                        
                        return $text;
                    }
                    $zip->close();
                }
                
                // If the ZIP extraction failed, try to use external tools if available
                if (function_exists('exec')) {
                    // Try using LibreOffice to convert to text if available
                    $outputPath = storage_path('app/tmp/extracted_' . pathinfo($filePath, PATHINFO_FILENAME) . '.txt');
                    @exec("libreoffice --headless --convert-to txt:Text --outdir " . storage_path('app/tmp') . " " . escapeshellarg($filePath) . " 2>/dev/null", $output, $returnVar);
                    
                    if ($returnVar === 0 && file_exists($outputPath)) {
                        return file_get_contents($outputPath);
                    }
                }
            }
            
            // For plain text files
            if (in_array(strtolower($extension), ['txt', 'csv', 'json', 'xml', 'html', 'htm', 'md', 'rtf'])) {
                return file_get_contents($filePath);
            }
            
            // For other file types, try to read as text if the file is not too large
            if (filesize($filePath) < 1024 * 1024 * 5) { // 5MB limit
                $content = file_get_contents($filePath);
                if (mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true)) {
                    return $content;
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error extracting text from file: " . $e->getMessage());
        }
        
        return '';
    }
    
    private function insertTotable($request, $aiResponse){
        // Check if we're editing an existing patient
        if ($request->edit_patient_id) {
            $patientToEdit = PatientAnalysis::find($request->edit_patient_id);
            
            if ($patientToEdit && $patientToEdit->user_id == auth()->id()) {
                // Update the existing patient record
                $patientToEdit->update([
                    'name' => $request->name,
                    'age' => $request->age,
                    'gender' => $request->gender,
                    'weight' => is_numeric($request->weight) ? $request->weight : null,
                    'height' => is_numeric($request->height) ? $request->height : null,
                    'temperature' => is_numeric($request->temperature) ? $request->temperature : null,
                    'blood_pressure' => $request->blood_pressure,
                    'blood_sugar' => is_numeric($request->blood_sugar) ? $request->blood_sugar : null,
                    'symptoms' => $request->current_symptoms ? json_encode($request->current_symptoms) : null,
                    'test_results' => $request->test_results,
                    'preliminary_diagnosis' => $request->preliminary_diagnosis,
                    'ai_response' => $aiResponse
                ]);
                
                return;
            }
        }
        
        // Check if we're using an existing patient for a new visit
        if ($request->patient_selection && $request->patient_selection != 'new') {
            // Get the existing patient data
            $existingPatient = PatientAnalysis::find($request->patient_selection);
            
            if ($existingPatient) {
                // Get the patient's history to determine the visit number
                $patientHistory = $existingPatient->getPatientHistory();
                $visitNumber = $patientHistory->count() + 1;
                
                // Generate or use existing patient key
                $patientKey = $existingPatient->patient_key ?? 
                    PatientAnalysis::generatePatientKey(
                        $existingPatient->name, 
                        $existingPatient->age, 
                        $existingPatient->gender, 
                        auth()->id()
                    );
                
                // If this is the first time we're using patient_key, update all previous records
                if (!$existingPatient->patient_key) {
                    foreach ($patientHistory as $index => $record) {
                        $record->update([
                            'patient_key' => $patientKey,
                            'visit_number' => $index + 1
                        ]);
                    }
                }
                
                // Create a new record with the existing patient's information
                // This creates a new entry in the patient history
                $newRecord = PatientAnalysis::create([
                    'name' => $existingPatient->name,
                    'age' => $existingPatient->age,
                    'gender' => $existingPatient->gender,
                    'weight' => is_numeric($request->weight) ? $request->weight : $existingPatient->weight,
                    'height' => is_numeric($request->height) ? $request->height : $existingPatient->height,
                    'temperature' => is_numeric($request->temperature) ? $request->temperature : null,
                    'blood_pressure' => $request->blood_pressure,
                    'blood_sugar' => is_numeric($request->blood_sugar) ? $request->blood_sugar : null,
                    'symptoms' => json_encode($this->processSymptoms($request->current_symptoms, $request->custom_symptoms)),
                    'test_results' => $request->test_results,
                    'preliminary_diagnosis' => $request->preliminary_diagnosis,
                    'ai_response' => $aiResponse,
                    'user_id' => auth()->id(),
                    'previous_record_id' => $existingPatient->id,
                    'visit_number' => $visitNumber,
                    'patient_key' => $patientKey
                ]);
                
                return;
            }
        }
        
        // New patient
        $patientKey = PatientAnalysis::generatePatientKey(
            $request->name, 
            $request->age, 
            $request->gender, 
            auth()->id()
        );
        
        PatientAnalysis::create([
            'name' => $request->name,
            'age' => $request->age,
            'gender' => $request->gender,
            'weight' => is_numeric($request->weight) ? $request->weight : null,
            'height' => is_numeric($request->height) ? $request->height : null,
            'temperature' => is_numeric($request->temperature) ? $request->temperature : null,
            'blood_pressure' => $request->blood_pressure,
            'blood_sugar' => is_numeric($request->blood_sugar) ? $request->blood_sugar : null,
            'symptoms' => json_encode($this->processSymptoms($request->current_symptoms, $request->custom_symptoms)),
            'test_results' => $request->test_results,
            'preliminary_diagnosis' => $request->preliminary_diagnosis,
            'ai_response' => $aiResponse,
            'user_id' => auth()->id(),
            'previous_record_id' => null, // No previous record for new patients
            'visit_number' => 1, // First visit
            'patient_key' => $patientKey
        ]);
    }

    private function preparePrompt($inputData, $criterion, $useFileSearch = false)
    {
        $fileSearchInstruction = $useFileSearch
            ? "CRITICAL INSTRUCTION: Thoroughly analyze ALL uploaded files before responding. 
            
            Add a 'PATIENT INFORMATION' section at the beginning of your response that includes:
            
            PATIENT INFORMATION:
            • Basic patient details from the input data
            • MEDICAL REPORTS ANALYSIS: A concise summary of all uploaded files
              - For images: Brief description and key findings
              - For text documents: Key medical information and relevance
            
            Keep this section concise even if there are many files (10+). Group similar files together and summarize.
            
            Your diagnosis and recommendations MUST be based primarily on the content of the uploaded files.
            DO NOT provide a generic response - your analysis should directly reference specific findings from the uploaded files."
            : "";
            
        // Get the user's specialty
        $specialty = auth()->user()->setting->specialty ?? null;
        
        $specialtyInstruction = "";
        if ($specialty) {
            // Ensure specialty is treated as a string
            $specialtyStr = (string)$specialty;
            
            $specialtyInstruction = "You are a senior consultant physician specialized in {$specialtyStr} with 20+ years of clinical experience. Your expertise in this field should guide your analysis and recommendations. 
            
            As a {$specialtyStr} specialist:
            1. Prioritize diagnoses that are most relevant to your specialty, with special attention to life-threatening conditions
            2. Provide specialty-specific insights that a general practitioner might miss
            3. Recommend specialized tests and procedures appropriate for your field
            4. Suggest evidence-based treatment approaches that reflect current best practices in {$specialtyStr}
            5. Highlight any red flags or warning signs particularly important in your specialty
            6. Use precise medical terminology and references that would be familiar to specialists in your field
            7. Be precise, specific, and actionable in your recommendations, as expected from a specialist
            
            Focus particularly on aspects of the case that relate to your specialty, but maintain a holistic view of the patient's condition.";
        }
        
        // Add patient history context if available
        $patientHistoryContext = "";
        if (isset($inputData['is_existing_patient']) && $inputData['is_existing_patient'] && isset($inputData['visit_count']) && $inputData['visit_count'] > 0) {
            $visitNumber = $inputData['current_visit_number'] ?? ($inputData['visit_count'] + 1);
            
            $patientHistoryContext = "
            PATIENT HISTORY CONTEXT:
            This is visit #" . $visitNumber . " for this patient.
            " . $inputData['previous_medical_history'] . "
            
            Please consider this patient history in your analysis. Compare current symptoms with previous visits and note any changes or patterns.
            ";
        }
        
        // Generate dynamic clinical context based on vital signs and symptoms
        $clinicalContext = $this->generateClinicalContext($inputData);

        return "Based on the provided information and considering the evaluation criteria from the selected source ($criterion), analyze the patient case and provide a detailed medical assessment.
            
            $fileSearchInstruction
            
            $specialtyInstruction
            
            $patientHistoryContext
            
            $clinicalContext
            
            IMPORTANT: Your analysis MUST be based on the clinical data provided and any uploaded files. Follow the exact output format specified below.
            
            🔶 OUTPUT FORMAT:
            You MUST return your response in the following structure:
            
            ---
            📋 PATIENT CASE SUMMARY:
            Name: {name}
            Age: {age}
            Gender: {gender}
            Height / Weight: {height} / {weight}
            Vitals:
            Temperature: {temperature} °C
            Blood Pressure: {bp} mmHg
            Blood Sugar: {sugar} mg/dL
            Heart Rate: {heart_rate if available} bpm
            Symptoms: {comma-separated list}
            Preliminary Diagnosis: {if available}
            Lab Results / Imaging: {or state 'Not Provided'}
            ---
            🚨 CASE URGENCY:
            ⚠️ {Emergency / Urgent / Routine}
            Brief reason why this triage level was chosen.
            ---
            🔬 DIFFERENTIAL DIAGNOSIS (Prioritized with Probabilities):
            Rank	Diagnosis	Probability (%)	Clinical Reasoning
            1	[Primary diagnosis]	[probability]	[reasoning with key findings supporting this diagnosis]
            2	[Secondary diagnosis]	[probability]	[reasoning with key findings supporting this diagnosis]
            3	[Tertiary diagnosis]	[probability]	[reasoning with key findings supporting this diagnosis]
            Add more rows as needed.
            ---
            🧪 RECOMMENDED INVESTIGATIONS:
            1. [Test name] — [Rationale]
            2. [Test name] — [Rationale]
            3. [Test name] — [Rationale]
            Add more as needed.
            ---
            💊 TREATMENT & MANAGEMENT RECOMMENDATIONS:
            Immediate Interventions:
            [List immediate steps]
            
            Medications:
            [List medications with dosages and frequencies]
            
            Referrals:
            [List any specialist referrals needed]
            ---
            ⚠️ WARNING SIGNS TO MONITOR:
            [List specific warning signs that would indicate deterioration]
            ---
            🧠 DOCTOR'S NOTE:
            [Brief clinical note with any additional context or considerations]
            ---
            📚 SOURCES:
            [List 3-5 relevant medical sources that support your recommendations]
            For each source, provide the title, author(s), publication year, and journal/source name.
            Include the URL to the source whenever possible (e.g., PubMed link, DOI, or journal website).
            Focus on high-quality, peer-reviewed sources from reputable medical journals.
            Prioritize recent publications (within the last 5 years when possible).
            Include at least one source specific to the primary diagnosis or recommendation.

            Here is the input data: " . json_encode($inputData);
    }


    public function getCases()
    {
        // Get all records belonging to the current user
        $records = PatientAnalysis::with('user')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Group records by patient_key to calculate total visits
        $patientGroups = [];
        
        foreach ($records as $record) {
            // If patient_key is not set, generate it and update the record
            if (!$record->patient_key) {
                $patientKey = PatientAnalysis::generatePatientKey(
                    $record->name, 
                    $record->age, 
                    $record->gender, 
                    $record->user_id
                );
                
                // Find all records for this patient
                $patientRecords = PatientAnalysis::where('name', $record->name)
                    ->where('age', $record->age)
                    ->where('gender', $record->gender)
                    ->where('user_id', $record->user_id)
                    ->orderBy('created_at', 'asc')
                    ->get();
                
                // Update all records with patient_key and visit_number
                foreach ($patientRecords as $index => $patientRecord) {
                    $patientRecord->update([
                        'patient_key' => $patientKey,
                        'visit_number' => $index + 1
                    ]);
                }
                
                // Update the current record in memory
                $record->patient_key = $patientKey;
            }
            
            // Group by patient_key
            if (!isset($patientGroups[$record->patient_key])) {
                $patientGroups[$record->patient_key] = [];
            }
            
            $patientGroups[$record->patient_key][] = $record;
        }
        
        // Calculate total_visits for each record
        foreach ($records as $record) {
            if (isset($patientGroups[$record->patient_key])) {
                $record->total_visits = count($patientGroups[$record->patient_key]);
            } else {
                $record->total_visits = 1;
            }
        }
        
        return view('cases', compact('records'));
    }
    public function dashboard()
    {
        // Filter records by the current authenticated user
        $records = PatientAnalysis::with('user')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Count only the current user's records for the past week
        $weeklyCount = PatientAnalysis::where('user_id', auth()->id())
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        
        // Prepare chart data safely in the controller
        $chartData = [];
        $chartLabels = [];
        
        if ($records->count() > 0) {
            $casesOverTime = $records->groupBy(function($record) {
                return $record->created_at->format('Y-m-d');
            })->map(function($group) {
                return $group->count();
            })->sortKeys();
            
            $chartLabels = $casesOverTime->keys()->toArray();
            $chartData = $casesOverTime->values()->toArray();
        }
        
        return view('dashboard', compact('records', 'weeklyCount', 'chartLabels', 'chartData'));
    }
    
    /**
     * Handle follow-up questions in the chat
     */
    public function followUp(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'conversation_id' => 'nullable|string'
        ]);
        
        $userMessage = $request->message;
        $conversationId = $request->conversation_id;
        
        // Get user's specialty and criterion
        $specialty = auth()->user()->setting->specialty ?? null;
        $criterion = auth()->user()->setting->criterion ?? 'CDC';
        
        // If we don't have a conversation ID, create a new conversation
        if (empty($conversationId)) {
            // Create a new conversation context
            $conversationId = uniqid('conv_');
            session(['conversation_id' => $conversationId]);
            
            // Store the conversation history in the session
            session(['conversation_history_' . $conversationId => [
                ['role' => 'system', 'content' => $this->getSystemPrompt($specialty, $criterion)],
                ['role' => 'user', 'content' => $userMessage]
            ]]);
        } else {
            // Get the existing conversation history
            $conversationHistory = session('conversation_history_' . $conversationId, [
                ['role' => 'system', 'content' => $this->getSystemPrompt($specialty, $criterion)]
            ]);
            
            // Add the user's message to the history
            $conversationHistory[] = ['role' => 'user', 'content' => $userMessage];
            
            // Update the conversation history in the session
            session(['conversation_history_' . $conversationId => $conversationHistory]);
        }
        
        // Get the full conversation history
        $messages = session('conversation_history_' . $conversationId);
        
        try {
            // Call the OpenAI API with the full conversation history
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => $messages
            ]);
            
            $aiResponse = $response['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.';
            
            // Add the AI's response to the conversation history
            $conversationHistory = session('conversation_history_' . $conversationId);
            $conversationHistory[] = ['role' => 'assistant', 'content' => $aiResponse];
            session(['conversation_history_' . $conversationId => $conversationHistory]);
            
            return response()->json([
                'success' => true,
                'message' => $aiResponse,
                'conversation_id' => $conversationId
            ]);
        } catch (\Exception $e) {
            // Check if it's an API key issue
            $message = $e->getMessage();
            if (strpos($message, 'API key') !== false || 
                strpos($message, 'authentication') !== false || 
                strpos($message, '401') !== false || 
                strpos($message, 'Unauthorized') !== false) {
                
                // It's likely an API key issue
                return response()->json([
                    'success' => false,
                    'message' => 'Your OpenAI API key appears to be invalid or expired. Please contact the administrator to update the API key.',
                    'api_key_error' => true
                ], 401);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate a summary of a patient's medical history
     */
    public function generatePatientSummary(Request $request)
    {
        \Log::info('generatePatientSummary called');
        
        $request->validate([
            'summary_data' => 'required|string',
        ]);
        
        try {
            // Decode the summary data
            $summaryData = json_decode($request->summary_data, true);
            \Log::info('Summary data decoded:', ['data' => $summaryData]);
            
            if (!$summaryData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid summary data format.'
                ], 400);
            }
            
            // Prepare the prompt for OpenAI
            $prompt = "Generate a comprehensive medical summary for the following patient based on their visit history:\n\n";
            $prompt .= "Patient: " . $summaryData['patient_name'] . "\n";
            $prompt .= "Age: " . $summaryData['patient_age'] . "\n";
            $prompt .= "Gender: " . $summaryData['patient_gender'] . "\n";
            $prompt .= "Total Visits: " . $summaryData['visit_count'] . "\n\n";
            $prompt .= "Visit History:\n";
            
            // Add instruction to not repeat patient information in the response
            $prompt .= "\nIMPORTANT: Do not include a 'Patient Information' section in your response. The patient's name, age, gender, and visit count are already displayed in the UI and should not be repeated in your summary. However, DO include the 'Current Symptoms' section as it contains important clinical information.\n";
            
            foreach ($summaryData['visits'] as $visit) {
                $prompt .= "Visit #" . $visit['visit_number'] . " (" . $visit['date'] . "):\n";
                $prompt .= $visit['ai_response'] . "\n\n";
            }
            
            $prompt .= "\nPlease provide a concise summary that includes:\n";
            $prompt .= "1. Overall health trajectory (improving, worsening, stable)\n";
            $prompt .= "2. Key medical issues identified across all visits\n";
            $prompt .= "3. Important trends in symptoms or test results\n";
            $prompt .= "4. Treatment effectiveness based on visit progression\n";
            $prompt .= "5. Recommendations for future care\n\n";
            $prompt .= "Format the summary with clear headings and bullet points where appropriate.";
            
            // Get user's specialty and criterion
            $specialty = auth()->user()->setting->specialty ?? null;
            $criterion = auth()->user()->setting->criterion ?? 'CDC';
            
            // Call OpenAI API
            \Log::info('Calling OpenAI API for summary generation');
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => $this->getSystemPrompt($specialty, $criterion)],
                    ['role' => 'user', 'content' => $prompt]
                ]
            ]);
            \Log::info('OpenAI API response received');
            
            $summary = $response['choices'][0]['message']['content'] ?? 'Failed to generate summary.';
            
            // Remove Patient Information section
            $summary = $this->removePatientInfoSection($summary);
            
            // Format the summary with proper HTML
            $lines = explode("\n", $summary);
            $formattedSummary = '';
            $inList = false;
            $listType = '';
            
            // Process each line
            foreach ($lines as $line) {
                // Check for headers (# Header)
                if (preg_match('/^#{1,6}\s+(.+)$/', $line, $matches)) {
                    if ($inList) {
                        $formattedSummary .= $listType === 'ul' ? '</ul>' : '</ol>';
                        $inList = false;
                    }
                    $formattedSummary .= '<h4>' . $matches[1] . '</h4>';
                }
                // Check for bullet points (* Item or - Item)
                else if (preg_match('/^[\s]*[\*\-]\s+(.+)$/', $line, $matches)) {
                    if (!$inList || $listType !== 'ul') {
                        if ($inList) $formattedSummary .= $listType === 'ul' ? '</ul>' : '</ol>';
                        $formattedSummary .= '<ul>';
                        $inList = true;
                        $listType = 'ul';
                    }
                    $formattedSummary .= '<li>' . $matches[1] . '</li>';
                }
                // Check for numbered lists (1. Item)
                else if (preg_match('/^[\s]*\d+\.\s+(.+)$/', $line, $matches)) {
                    if (!$inList || $listType !== 'ol') {
                        if ($inList) $formattedSummary .= $listType === 'ul' ? '</ul>' : '</ol>';
                        $formattedSummary .= '<ol>';
                        $inList = true;
                        $listType = 'ol';
                    }
                    $formattedSummary .= '<li>' . $matches[1] . '</li>';
                }
                // Regular text
                else {
                    if ($inList) {
                        $formattedSummary .= $listType === 'ul' ? '</ul>' : '</ol>';
                        $inList = false;
                    }
                    
                    // Skip empty lines
                    if (trim($line) === '') {
                        $formattedSummary .= '<br>';
                        continue;
                    }
                    
                    // Check for section headers with multiple patterns
                    if (preg_match('/^[A-Z][\)\.]?\s+.*?(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i', $line) || 
                        preg_match('/^(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i', $line) ||
                        preg_match('/^[A-Z]\)\s+(POSSIBLE\s+DIAGNOS[IE]S|RECOMMENDATIONS\s+FOR\s+TESTS|TREATMENT\s+RECOMMENDATIONS|WARNINGS)$/i', $line)) {
                        $className = '';
                        if (preg_match('/DIAGNOS[IE]S/i', $line)) {
                            $className = 'section-diagnosis';
                        } elseif (preg_match('/RECOMMENDATIONS/i', $line)) {
                            $className = 'section-recommendations';
                        } elseif (preg_match('/TREATMENT/i', $line)) {
                            $className = 'section-treatment';
                        } elseif (preg_match('/WARNINGS/i', $line)) {
                            $className = 'section-warnings';
                        }
                        
                        $formattedSummary .= '<p><strong class="' . $className . '">' . $line . '</strong></p>';
                    } else {
                        // All other text is formatted as regular paragraphs
                        $formattedSummary .= '<p>' . $line . '</p>';
                    }
                }
            }
            
            // Close any open lists
            if ($inList) {
                $formattedSummary .= $listType === 'ul' ? '</ul>' : '</ol>';
            }
            
            // Process inline formatting
            
            // Bold text between ** or __
            $formattedSummary = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $formattedSummary);
            $formattedSummary = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $formattedSummary);
            
            // Italic text between * or _
            $formattedSummary = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $formattedSummary);
            $formattedSummary = preg_replace('/_([^_]+)_/', '<em>$1</em>', $formattedSummary);
            
            // Section headers are now handled during line processing
            
            // Wrap in ai-content div
            $formattedSummary = '<div class="ai-content">' . $formattedSummary . '</div>';
            
            return response()->json([
                'success' => true,
                'summary' => $formattedSummary
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in generatePatientSummary: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error generating summary: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get the system prompt based on user's specialty and criterion
     */
    private function getSystemPrompt($specialty, $criterion)
    {
        $specialtyInstruction = "";
        if ($specialty) {
            // Ensure specialty is treated as a string
            $specialtyStr = (string)$specialty;
            
            $specialtyInstruction = "You are a senior consultant physician specialized in {$specialtyStr} with 20+ years of clinical experience. Your expertise in this field should guide your analysis and recommendations. 
            
            As a {$specialtyStr} specialist:
            1. Prioritize diagnoses that are most relevant to your specialty, with special attention to life-threatening conditions
            2. Provide specialty-specific insights that a general practitioner might miss
            3. Recommend specialized tests and procedures appropriate for your field
            4. Suggest evidence-based treatment approaches that reflect current best practices in {$specialtyStr}
            5. Highlight any red flags or warning signs particularly important in your specialty
            6. Use precise medical terminology and references that would be familiar to specialists in your field
            7. Be precise, specific, and actionable in your recommendations, as expected from a specialist
            
            Focus particularly on aspects of the case that relate to your specialty, but maintain a holistic view of the patient's condition.";
        }
        
        return "You are an advanced clinical AI working inside a professional medical SaaS platform called MedCuraAI. Your job is to act like a highly experienced emergency/internal medicine physician who is cautious, evidence-based, and prioritizes patient safety.
        Based on the evaluation criteria from $criterion, provide precise, evidence-based clinical assessments.
        $specialtyInstruction
        
        CRITICAL CLINICAL APPROACH:
        1. ALWAYS prioritize life-threatening conditions first in your differential diagnosis
        2. Assign specific probability percentages to each diagnosis (e.g., 70%, 25%, 5%)
        3. Provide clear clinical reasoning for each diagnosis
        4. Use proper medical terminology throughout your assessment
        5. Be specific and detailed in your recommendations
        6. NEVER be vague or overly reassuring about serious symptoms
        7. Flag cases as ROUTINE, URGENT, or EMERGENCY based on clinical presentation
        8. Include specific medication recommendations when appropriate
        9. Recommend specialist referrals when indicated
        
        IMPORTANT INSTRUCTIONS FOR ANALYZING UPLOADED FILES:
        When files are uploaded, include a 'MEDICAL REPORTS ANALYSIS' subsection within the PATIENT CASE SUMMARY with a concise summary of all uploaded files:
        - Keep it concise even if there are many files (10+)
        - Group similar files together and summarize key findings
        - For images: Brief description and key findings
        - For text documents: Key medical information and relevance
        
        🔶 OUTPUT FORMAT:
        You MUST return your response in the following structure:
        
        ---
        📋 PATIENT CASE SUMMARY:
        Name: {name}
        Age: {age}
        Gender: {gender}
        Height / Weight: {height} / {weight}
        Vitals:
        Temperature: {temperature} °C
        Blood Pressure: {bp} mmHg
        Blood Sugar: {sugar} mg/dL
        Heart Rate: {heart_rate if available} bpm
        Symptoms: {comma-separated list}
        Preliminary Diagnosis: {if available}
        Lab Results / Imaging: {or state 'Not Provided'}
        ---
        🚨 CASE URGENCY:
        ⚠️ {Emergency / Urgent / Routine}
        Brief reason why this triage level was chosen.
        ---
        🔬 DIFFERENTIAL DIAGNOSIS (Prioritized with Probabilities):
        Rank	Diagnosis	Probability (%)	Clinical Reasoning
        1	[Primary diagnosis]	[probability]	[reasoning with key findings supporting this diagnosis]
        2	[Secondary diagnosis]	[probability]	[reasoning with key findings supporting this diagnosis]
        3	[Tertiary diagnosis]	[probability]	[reasoning with key findings supporting this diagnosis]
        Add more rows as needed.
        ---
        🧪 RECOMMENDED INVESTIGATIONS:
        1. [Test name] — [Rationale]
        2. [Test name] — [Rationale]
        3. [Test name] — [Rationale]
        Add more as needed.
        ---
        💊 TREATMENT & MANAGEMENT RECOMMENDATIONS:
        Immediate Interventions:
        [List immediate steps]
        
        Medications:
        [List medications with dosages and frequencies]
        
        Referrals:
        [List any specialist referrals needed]
        ---
        ⚠️ WARNING SIGNS TO MONITOR:
        [List specific warning signs that would indicate deterioration]
        ---
        🧠 DOCTOR'S NOTE:
        [Brief clinical note with any additional context or considerations]
        ---
        📚 SOURCES:
        [List 3-5 relevant medical sources that support your recommendations]
        For each source, provide the title, author(s), publication year, and journal/source name.
        Include the URL to the source whenever possible (e.g., PubMed link, DOI, or journal website).
        Focus on high-quality, peer-reviewed sources from reputable medical journals.
        Prioritize recent publications (within the last 5 years when possible).
        Include at least one source specific to the primary diagnosis or recommendation.";
    }
    
    /**
     * Process both regular and custom symptoms
     * 
     * @param array $symptoms Array of symptom IDs from the dropdown
     * @param string|null $customSymptoms JSON string of custom symptoms
     * @return array Processed symptoms array
     */
    private function processSymptoms($symptoms, $customSymptoms = null)
    {
        \Log::info("Processing symptoms: " . json_encode($symptoms));
        \Log::info("Custom symptoms: " . $customSymptoms);
        
        $processedSymptoms = [];
        
        // Process regular symptoms (IDs from dropdown)
        if (is_array($symptoms)) {
            foreach ($symptoms as $symptom) {
                \Log::info("Processing regular symptom: " . json_encode($symptom) . " (type: " . gettype($symptom) . ")");
                
                // Check if the symptom is a numeric ID (predefined symptom)
                if (is_numeric($symptom)) {
                    // Try to find the symptom in the database
                    $symptomModel = \App\Models\Symptom::find($symptom);
                    if ($symptomModel) {
                        \Log::info("Found predefined symptom: " . $symptomModel->name);
                        $processedSymptoms[] = $symptomModel->name;
                    } else {
                        // If not found, just add the ID as is
                        \Log::warning("Symptom ID not found in database: " . $symptom);
                        $processedSymptoms[] = $symptom;
                    }
                } else {
                    // This is a custom symptom (text), add it directly
                    \Log::info("Adding custom symptom from dropdown: " . $symptom);
                    $processedSymptoms[] = $symptom;
                }
            }
        }
        
        // Process custom symptoms (from the custom input)
        if (!empty($customSymptoms)) {
            try {
                $customSymptomsArray = json_decode($customSymptoms, true);
                
                if (is_array($customSymptomsArray)) {
                    foreach ($customSymptomsArray as $customSymptom) {
                        \Log::info("Processing custom symptom: " . $customSymptom);
                        
                        // Add the custom symptom to the processed list
                        $processedSymptoms[] = $customSymptom;
                        
                        // Save the new symptom to the database for future use
                        try {
                            $newSymptom = \App\Models\Symptom::firstOrCreate(['name' => $customSymptom]);
                            \Log::info("Saved custom symptom to database with ID: " . $newSymptom->id);
                        } catch (\Exception $e) {
                            // Log the error but continue processing
                            \Log::error("Error saving custom symptom: " . $e->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Error processing custom symptoms: " . $e->getMessage());
            }
        }
        
        \Log::info("Final processed symptoms: " . json_encode($processedSymptoms));
        return $processedSymptoms;
    }
    
    /**
     * Generate dynamic clinical context based on vital signs and symptoms
     * This enhances the prompt with specific medical considerations based on the patient's data
     * 
     * @param array $inputData Patient data including vital signs and symptoms
     * @return string Clinical context for the prompt
     */
    private function generateClinicalContext($inputData)
    {
        $context = "CLINICAL CONTEXT:\n";
        $abnormalFindings = [];
        $redFlags = [];
        
        // Check vital signs for abnormalities
        if (!empty($inputData['temperature'])) {
            $temp = floatval($inputData['temperature']);
            if ($temp > 38.0) {
                $abnormalFindings[] = "Fever (T: {$temp}°C)";
                if ($temp > 39.5) {
                    $redFlags[] = "High-grade fever (T: {$temp}°C)";
                }
            } else if ($temp < 36.0) {
                $abnormalFindings[] = "Hypothermia (T: {$temp}°C)";
                if ($temp < 35.0) {
                    $redFlags[] = "Significant hypothermia (T: {$temp}°C)";
                }
            }
        }
        
        // Check blood pressure
        if (!empty($inputData['blood_pressure'])) {
            $bp = $inputData['blood_pressure'];
            // Try to parse systolic/diastolic values
            if (preg_match('/(\d+)[\/\s]+(\d+)/', $bp, $matches)) {
                $systolic = intval($matches[1]);
                $diastolic = intval($matches[2]);
                
                if ($systolic >= 180 || $diastolic >= 120) {
                    $redFlags[] = "Hypertensive crisis (BP: {$bp})";
                } else if ($systolic >= 140 || $diastolic >= 90) {
                    $abnormalFindings[] = "Hypertension (BP: {$bp})";
                } else if ($systolic < 90 || $diastolic < 60) {
                    $abnormalFindings[] = "Hypotension (BP: {$bp})";
                    if ($systolic < 80) {
                        $redFlags[] = "Severe hypotension (BP: {$bp})";
                    }
                }
            }
        }
        
        // Check blood sugar
        if (!empty($inputData['blood_sugar'])) {
            $bs = floatval($inputData['blood_sugar']);
            // Assuming mg/dL as the unit
            if ($bs > 180) {
                $abnormalFindings[] = "Hyperglycemia (BS: {$bs} mg/dL)";
                if ($bs > 300) {
                    $redFlags[] = "Severe hyperglycemia (BS: {$bs} mg/dL)";
                }
            } else if ($bs < 70) {
                $abnormalFindings[] = "Hypoglycemia (BS: {$bs} mg/dL)";
                if ($bs < 54) {
                    $redFlags[] = "Severe hypoglycemia (BS: {$bs} mg/dL)";
                }
            }
        }
        
        // Check symptoms for red flags
        $emergencySymptoms = [
            'chest pain', 'shortness of breath', 'difficulty breathing', 'severe headache', 
            'sudden confusion', 'slurred speech', 'facial drooping', 'weakness in limbs',
            'loss of consciousness', 'seizure', 'severe abdominal pain', 'vomiting blood',
            'black stool', 'bloody stool', 'severe bleeding', 'trauma', 'head injury',
            'suicidal', 'suicide', 'homicidal', 'homicide', 'psychosis', 'hallucinations',
            'delusions', 'paralysis', 'unable to move', 'stroke', 'heart attack', 'cardiac arrest'
        ];
        
        // Get symptoms from the input data
        $symptoms = [];
        if (!empty($inputData['symptoms'])) {
            if (is_array($inputData['symptoms'])) {
                $symptoms = $inputData['symptoms']; // Already processed
            } else if (is_string($inputData['symptoms'])) {
                // Handle case where symptoms might be a JSON string
                $decodedSymptoms = json_decode($inputData['symptoms'], true);
                if (is_array($decodedSymptoms)) {
                    $symptoms = $decodedSymptoms;
                }
            }
        }
        
        // Check for emergency symptoms
        foreach ($symptoms as $symptom) {
            foreach ($emergencySymptoms as $emergencySymptom) {
                if (stripos($symptom, $emergencySymptom) !== false) {
                    $redFlags[] = "Emergency symptom: {$symptom}";
                    break;
                }
            }
        }
        
        // Add abnormal findings to context
        if (!empty($abnormalFindings)) {
            $context .= "Abnormal clinical findings:\n";
            foreach ($abnormalFindings as $finding) {
                $context .= "• {$finding}\n";
            }
            $context .= "\n";
        }
        
        // Add red flags with special emphasis
        if (!empty($redFlags)) {
            $context .= "RED FLAGS - REQUIRE IMMEDIATE ATTENTION:\n";
            foreach ($redFlags as $flag) {
                $context .= "• {$flag}\n";
            }
            $context .= "\nThese red flags must be addressed as high priority in your differential diagnosis and management plan.\n";
        }
        
        // Add age-specific considerations
        if (!empty($inputData['age'])) {
            $age = intval($inputData['age']);
            
            if ($age < 2) {
                $context .= "\nPEDIATRIC CONSIDERATIONS (Infant):\n";
                $context .= "• Consider age-appropriate differential diagnoses\n";
                $context .= "• Adjust medication dosages based on weight\n";
                $context .= "• Be vigilant for congenital conditions\n";
            } else if ($age < 12) {
                $context .= "\nPEDIATRIC CONSIDERATIONS (Child):\n";
                $context .= "• Consider age-appropriate differential diagnoses\n";
                $context .= "• Adjust medication dosages based on weight\n";
            } else if ($age > 65) {
                $context .= "\nGERIATRIC CONSIDERATIONS:\n";
                $context .= "• Consider polypharmacy and drug interactions\n";
                $context .= "• Be vigilant for atypical presentations of common conditions\n";
                $context .= "• Consider fall risk in medication recommendations\n";
            }
        }
        
        // If no specific context was generated, return empty string
        if ($context === "CLINICAL CONTEXT:\n") {
            return "";
        }
        
        return $context;
    }
    
    /**
     * Format the AI response to ensure it follows our structured format
     * This helps standardize the output and ensure all required sections are present
     */
    private function formatResponseStructure($response)
    {
        // Check if the response already has a CASE URGENCY section
        if (!preg_match('/CASE URGENCY:\s*(ROUTINE|URGENT|EMERGENCY)/i', $response)) {
            // Try to determine urgency based on content
            $urgencyLevel = "ROUTINE";
            
            // Check for emergency keywords
            if (preg_match('/(emergency|immediate attention|life-threatening|critical|severe|urgent intervention|stat)/i', $response)) {
                $urgencyLevel = "EMERGENCY";
            } 
            // Check for urgent keywords
            else if (preg_match('/(urgent|prompt attention|soon|timely|priority)/i', $response)) {
                $urgencyLevel = "URGENT";
            }
            
            // Add the urgency section at the beginning
            $response = "CASE URGENCY: $urgencyLevel\n\n" . $response;
        }
        
        // Ensure section headers are properly formatted
        $sections = [
            'PATIENT INFORMATION' => 'PATIENT INFORMATION:',
            'DIFFERENTIAL DIAGNOSIS' => 'A) DIFFERENTIAL DIAGNOSIS (PRIORITIZED):',
            'RECOMMENDED INVESTIGATIONS' => 'B) RECOMMENDED INVESTIGATIONS:',
            'MANAGEMENT RECOMMENDATIONS' => 'C) MANAGEMENT RECOMMENDATIONS:',
            'CLINICAL CONSIDERATIONS' => 'D) CLINICAL CONSIDERATIONS & PRECAUTIONS:'
        ];
        
        foreach ($sections as $keyword => $formattedHeader) {
            // Check if a section with this keyword exists but isn't properly formatted
            if (preg_match('/(?:^|\n)(?!.*' . preg_quote($formattedHeader, '/') . ').*' . preg_quote($keyword, '/') . '.*(?:\n|:)/i', $response)) {
                // Replace the improperly formatted header with the correct one
                $response = preg_replace('/(?:^|\n)(?!.*' . preg_quote($formattedHeader, '/') . ').*' . preg_quote($keyword, '/') . '.*(?:\n|:)/i', "\n\n" . $formattedHeader . "\n", $response);
            }
            // If the section doesn't exist at all, we don't add it as it might not be applicable
        }
        
        // Ensure the old A) POSSIBLE DIAGNOSIS section is renamed to our new format
        if (preg_match('/(?:^|\n)A\)\s*POSSIBLE\s*DIAGNOSIS/i', $response) && !preg_match('/DIFFERENTIAL DIAGNOSIS/i', $response)) {
            $response = preg_replace('/(?:^|\n)A\)\s*POSSIBLE\s*DIAGNOSIS.*(?:\n|:)/i', "\n\nA) DIFFERENTIAL DIAGNOSIS (PRIORITIZED):\n", $response);
        }
        
        // Ensure the old B) RECOMMENDATIONS FOR TESTS section is renamed
        if (preg_match('/(?:^|\n)B\)\s*RECOMMENDATIONS\s*FOR\s*TESTS/i', $response) && !preg_match('/RECOMMENDED INVESTIGATIONS/i', $response)) {
            $response = preg_replace('/(?:^|\n)B\)\s*RECOMMENDATIONS\s*FOR\s*TESTS.*(?:\n|:)/i', "\n\nB) RECOMMENDED INVESTIGATIONS:\n", $response);
        }
        
        // Ensure the old C) TREATMENT RECOMMENDATIONS section is renamed
        if (preg_match('/(?:^|\n)C\)\s*TREATMENT\s*RECOMMENDATIONS/i', $response) && !preg_match('/MANAGEMENT RECOMMENDATIONS/i', $response)) {
            $response = preg_replace('/(?:^|\n)C\)\s*TREATMENT\s*RECOMMENDATIONS.*(?:\n|:)/i', "\n\nC) MANAGEMENT RECOMMENDATIONS:\n", $response);
        }
        
        // Ensure the old D) WARNING SIGNS section is renamed
        if (preg_match('/(?:^|\n)D\)\s*WARNING\s*SIGNS/i', $response) && !preg_match('/CLINICAL CONSIDERATIONS/i', $response)) {
            $response = preg_replace('/(?:^|\n)D\)\s*WARNING\s*SIGNS.*(?:\n|:)/i', "\n\nD) CLINICAL CONSIDERATIONS & PRECAUTIONS:\n", $response);
        }
        
        // Add styling for probability percentages to make them stand out
        $response = preg_replace('/(\d{1,3})%/i', '<strong>$1%</strong>', $response);
        
        // Highlight emergency warnings
        $response = preg_replace('/(emergency|immediate attention needed|life-threatening|critical condition)/i', '<span style="color: red; font-weight: bold;">$1</span>', $response);
        
        // Highlight urgent/emergency case urgency
        $response = preg_replace('/(CASE URGENCY:\s*)(URGENT|EMERGENCY)/i', '$1<span style="color: red; font-weight: bold;">$2</span>', $response);
        
        return $response;
    }
}
