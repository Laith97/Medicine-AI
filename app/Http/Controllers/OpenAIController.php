<?php

namespace App\Http\Controllers;

use App\Models\PatientAnalysis;
use App\Models\Symptom;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIController extends Controller

{
    public function showForm()
    {
        $symptoms = Symptom::all();
        

        return view('openai', compact('symptoms'));
    }

    
    public function getResponse(Request $request)
    {
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
                    // Upload as file for file_search    
                    $uploaded = OpenAI::files()->upload([
                        'purpose' => 'assistants',
                        'file' => fopen($tempPath, 'r'),
                    ]);
    
                    $uploadedFileIds[] = $uploaded['id'];
                }
            }
        }
    
        // GPT-4 Vision
        if (!empty($imageMessages)) {
            $response = OpenAI::chat()->create([
               'model' => 'gpt-4o-mini',

                'messages' => [
                    [
                        'role' => 'user',
                        'content' => array_merge(
                            [['type' => 'text', 'text' => $this->preparePrompt($inputData, $criterion, false)]],
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
    
            $assistant = OpenAI::assistants()->create([
                'name' => 'Medical Report Analyzer',
                'instructions' => 'You are a helpful assistant that analyzes medical reports.',
                'tools' => [['type' => 'file_search']],
                'tool_resources' => [
                    'file_search' => [
                        'vector_store_ids' => [$vectorStoreId],
                    ],
                ],
                'model' => 'gpt-4o-mini',
            ]);
    
            $thread = OpenAI::threads()->create([]);
            $threadId = $thread['id'];
    
            OpenAI::threads()->messages()->create($threadId, [
                'role' => 'user',
                'content' => $this->preparePrompt($inputData, $criterion, true),
            ]);
    
            $run = OpenAI::threads()->runs()->create($threadId, [
                'assistant_id' => $assistant['id'],
            ]);
            $runId = $run['id'];
            

            return $this->checkRunStatus($request, $threadId, $runId);
        }
    
        // No files provided: still try to respond based on inputData alone
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
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
                            $this->insertTotable($request,$filteredMessage);

        
        
        return redirect()->back()->with([
            'openai_result' => $filteredMessage,
        ]);
        
    }

    
    
    
    public function checkRunStatus($request, $threadId, $runId)
    {
        $maxAttempts = 20;
        $delayMicroseconds = 500000; // 0.5 seconds
    
        for ($i = 0; $i < $maxAttempts; $i++) {
            $runStatus = OpenAI::threads()->runs()->retrieve($threadId, $runId);

            if ($runStatus['status'] === 'completed') {
                $messages = OpenAI::threads()->messages()->list($threadId);
    
                $lastMessage = $messages['data'][0]['content'][0]['text']['value'] ?? 'No content available';
                
                $lastMessage = $this->filterReponse($lastMessage);
                
               $this->insertTotable($request,$lastMessage);

                
                return redirect()->back()->with([
                    'openai_result' => $lastMessage,
                ]);
            }
    
            usleep($delayMicroseconds); // more responsive than sleep()
        }
    
        return redirect()->back()->with([
            'openai_error' => 'The analysis is still in progress. Please try again later.',
        ]);
    }
    
    

    private function collectPatientData(Request $request)
    {
        return [
            'age' => $request->age,
            'gender' => $request->gender,
            'symptoms' => $request->current_symptoms,
            'test_results' => $request->test_results,
            'clinical_status' => [
                'temperature' => $request->temperature,
                'blood_pressure' => $request->blood_pressure,
                'blood_sugar' => $request->blood_sugar,
            ],
            'reports' => $request->file('reports') ?? null,
            'preliminary_diagnosis' => $request->preliminary_diagnosis,
        ];
    }

    private function filterReponse($lastMessage)
    {
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
        
    
        // Trim content before "A) POSSIBLE DIAGNOSIS:"
        $pattern = '/A\)\s*POSSIBLE\s*DIAGNOSIS:?/i';
        if (preg_match($pattern, $lastMessage, $match, PREG_OFFSET_CAPTURE)) {
            $startPos = $match[0][1];
            $lastMessage = substr($lastMessage, $startPos);
        }
    
        // Final trim
        return trim($lastMessage);
    }
    private function insertTotable($request,$aiResponse){
        PatientAnalysis::create([
            'name' => $request->name,
            'age' => $request->age,
            'gender' => $request->gender,
            'weight' => $request->weight, // Nullable, so it's safe to leave blank
            'height' => $request->height, // Nullable, so it's safe to leave blank
            'temperature' => $request->temperature, // Nullable, so it's safe to leave blank
            'blood_pressure' => $request->blood_pressure, // Nullable, so it's safe to leave blank
            'blood_sugar' => $request->blood_sugar, // Nullable, so it's safe to leave blank
            'symptoms' => $request->symptoms ? json_encode($request->symptoms) : null, // Assuming symptoms are an array
            'test_results' => $request->test_results,
            'preliminary_diagnosis' => $request->preliminary_diagnosis,
            'ai_response' => $aiResponse, // AI response from previous step
            'user_id' => auth()->id(),
        ]);
    }

    private function preparePrompt($inputData, $criterion, $useFileSearch = false)
    {
        $fileSearchInstruction = $useFileSearch
            ? "Additionally, search through the provided files to gather any relevant information supporting the diagnosis or recommendations."
            : "";

        return "Based on the provided symptoms and test results and considering the evaluation criteria from the selected source ($criterion), provide the following:
            A) Possible diagnosis:
            • A list of potential diseases ranked by priority.
            • Display probability percentages (e.g., 70% viral infection, 20% bacterial).
            B) Recommendations for tests or imaging:
            • A list of tests or procedures that can help confirm the diagnosis.
            C) Treatment recommendations:
            • Tips on initial treatments or procedures (if necessary).
            D) Warnings:
            • About unnecessary procedures (to avoid over-treatment).

            $fileSearchInstruction

            Here is the input data: " . json_encode($inputData);
    }


    public function getCases()
    {
        $records = PatientAnalysis::with('user')->get();
        return view('cases', compact('records'));
    }
    public function dashboard()
    {
        $records = PatientAnalysis::with('user')->get();
        return view('dashboard', compact('records'));
    }
    
}
