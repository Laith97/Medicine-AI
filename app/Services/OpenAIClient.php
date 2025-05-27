<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OpenAIClient
{
    protected $client;

    public function __construct()
    {
        $this->client = Http::timeout(30) // Increase timeout for slower connections
            ->withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
                'OpenAI-Beta' => 'assistants=v2',
                'Content-Type' => 'application/json',
            ])
            ->baseUrl('https://api.openai.com/v1');
    }

    /**
     * Ask a simple prompt (Chat Completions).
     */

     public function postToOpenAI(string $endpoint, array $payload)
{
    return $this->client->post($endpoint, $payload);
}
    public function ask(string $prompt)
    {
        $response = $this->client->post('/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
        ]);

        return $response->json('choices.0.message.content');
    }

    /**
     * Upload file to OpenAI using /v1/uploads (newer method).
     */
    public function uploadFile(UploadedFile $file)
    {
        try {
            // Step 1: Get the file path
            $filePath = $file->getRealPath();
            $fileName = $file->getClientOriginalName();
            
            // Step 2: Read the file and prepare the data in prompt-completion format
            $names = file($filePath, FILE_IGNORE_NEW_LINES);  // Read file lines into an array
            
            // Step 3: Convert names into prompt-completion format
            $jsonlData = [];
            foreach ($names as $name) {
                $jsonlData[] = json_encode([
                    'prompt' => "Who is $name?", 
                    'completion' => $name
                ]);
            }
            
            // Step 4: Create a temporary .jsonl file to upload
            $jsonlFilePath = storage_path('app/temp_file.jsonl');
            file_put_contents($jsonlFilePath, implode("\n", $jsonlData)); // Write data to the .jsonl file
    
            // Step 5: Upload the .jsonl file to OpenAI for fine-tuning
            $response = Http::withToken(config('services.openai.key'))
                ->attach('file', fopen($jsonlFilePath, 'r'), 'fine_tuning_data.jsonl')
                ->post('https://api.openai.com/v1/files', [
                    'purpose' => 'fine-tune',
                ]);
            
            // Log the response for debugging
            \Log::error('Upload Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
    
            // Step 6: Handle the API response
            if ($response->successful()) {
                // Clean up the temporary file
                unlink($jsonlFilePath); // Delete the temporary .jsonl file
                
                // Return the file ID for further processing
                return $response->json('id');
            } else {
                // Return error details if the upload fails
                return response()->json([
                    'error' => 'Upload failed',
                    'status' => $response->status(),
                    'response' => $response->json(),
                ], $response->status());
            }
    
        } catch (\Exception $e) {
            \Log::error('Upload Exception', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'File upload failed.'], 500);
        }
    }
    
    
    
    
    

    /**
     * Create a new assistant thread with the prompt message.
     */
    public function createThreadWithMessage(string $prompt, array $fileIds = [])
    {
        try {
            $response = $this->client->post('/threads', [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                        'file_ids' => $fileIds,
                    ],
                ],
            ]);
    
            if ($response->successful()) {
                return $response->json('id');
            } else {
                // Return or log full details including raw response
                \Log::error('OpenAI upload error', [
                    'status' => $response->status(),
                    'body' => $response->body(), // This will show the actual error message
                ]);
                
                return response()->json([
                    'error' => 'Upload failed',
                    'status' => $response->status(),
                    'body' => $response->body(), // 👈 Return raw body directly
                ], $response->status());
            }
            
        } catch (\Exception $e) {
            Log::error('Create thread exception', ['error' => $e->getMessage()]);
            return null;
        }
    }
    

    /**
     * Start a run with assistant, thread, and file(s).
     */
    public function startRun(string $threadId, array $fileIds = [])
    {
        try {
            // Ensure $fileIds are provided (they should be passed from the controller)
            if (empty($fileIds)) {
                return response()->json(['error' => 'No file IDs provided.'], 422);
            }
    
            // Prepare the request data
            $data = [
                'instructions' => 'Analyze the uploaded file and the prompt.',
                'file_ids' => $fileIds,
            ];
    
            // Make the API request
            $response = $this->client->post("/threads/{$threadId}/runs", $data);
    
            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Start run failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Start run exception', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    
}
