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
     * Transcribe and diarize audio using GPT-4o Audio.
     * Returns structured JSON with speaker segments.
     */
    public function transcribeAndDiarizeWithGPT4o(string $audioPath, string $language = 'ar')
    {
        try {
            // Determine format based on extension
            $extension = pathinfo($audioPath, PATHINFO_EXTENSION);
            $isConverted = false;
            $originalPath = $audioPath;

            // gpt-4o-audio-preview is very specific about formats. webm is often problematic.
            // Convert to wav if it's not already wav or mp3
            if (!in_array(strtolower($extension), ['wav', 'mp3'])) {
                Log::info("GPT-4o Audio: Converting $extension to wav for compatibility", ['path' => $audioPath]);
                $wavPath = str_replace('.' . $extension, '_converted.wav', $audioPath);
                
                try {
                    // Use ffmpeg to convert to wav (16kHz, mono, pcm_s16le is standard and safe)
                    $command = "ffmpeg -i " . escapeshellarg($audioPath) . " -ar 16000 -ac 1 -c:a pcm_s16le " . escapeshellarg($wavPath) . " 2>&1";
                    exec($command, $output, $returnCode);

                    if ($returnCode === 0 && file_exists($wavPath)) {
                        $audioPath = $wavPath;
                        $extension = 'wav';
                        $isConverted = true;
                        Log::info("GPT-4o Audio: Conversion successful", ['new_path' => $audioPath]);
                    } else {
                        Log::error("GPT-4o Audio: Conversion failed", [
                            'return_code' => $returnCode,
                            'output' => implode("\n", is_array($output) ? $output : [])
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("GPT-4o Audio: Conversion exception", ['error' => $e->getMessage()]);
                }
            }

            $audioData = base64_encode(file_get_contents($audioPath));
            $format = $extension === 'webm' ? 'wav' : $extension; // Safely map common types

            $response = $this->client->post('/chat/completions', [
                'model' => 'gpt-4o-audio-preview',
                'modalities' => ['text'],
                'messages' => [
                    [
                        'role' => 'system', 
                        'content' => 'You are a medical transcription assistant. Listen to the audio and:
1. Transcribe accurately in ' . ($language === 'ar' ? 'Arabic' : 'the detected language') . '
2. Identify different speakers (Speaker 1, Speaker 2, etc.)
3. Return ONLY a JSON array with this EXACT format:
[{"speaker_tag": 1, "text": "first sentence", "start_time": 0.0}, {"speaker_tag": 2, "text": "second sentence", "start_time": 2.5}]

IMPORTANT: Return ONLY the JSON array, no other text.'
                    ],
                    [
                        'role' => 'user', 
                        'content' => [
                            [
                                'type' => 'input_audio',
                                'input_audio' => [
                                    'data' => $audioData,
                                    'format' => $format
                                ]
                            ],
                            [
                                'type' => 'text',
                                'text' => 'Transcribe this audio with speaker diarization. Return JSON array only.'
                            ]
                        ]
                    ]
                ]
            ]);

            // Cleanup converted file if created
            if ($isConverted && file_exists($audioPath)) {
                unlink($audioPath);
            }

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                
                // Try to parse as JSON array
                $decoded = json_decode($content, true);
                
                if (is_array($decoded) && count($decoded) > 0) {
                    // Check if it's already formatted segments
                    if (isset($decoded[0]['speaker_tag']) || isset($decoded[0]['text'])) {
                        Log::info("GPT-4o Audio: Transcription successful", ['segment_count' => count($decoded)]);
                        return $decoded;
                    }
                }
                
                // If not JSON or wrong format, treat as plain text and create a single segment
                Log::info("GPT-4o Audio: Plain text transcription, creating single segment", ['length' => strlen($content)]);
                return [[
                    'speaker_tag' => 1,
                    'text' => $content,
                    'start_time' => 0.0
                ]];
            }

            Log::error('OpenAI GPT-4o Audio API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI GPT-4o Audio exception', ['error' => $e->getMessage()]);
            // Cleanup on exception
            if (isset($isConverted) && $isConverted && isset($audioPath) && file_exists($audioPath)) {
                unlink($audioPath);
            }
            return null;
        }
    }

    /**
     * Transcribe audio using Whisper-1.
     */
    public function transcribeAudio(string $audioPath, string $language = null)
    {
        try {
            $params = [
                'model' => 'whisper-1',
                'response_format' => 'text'
            ];
            
            if ($language) {
                $params['language'] = $language;
            }

            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.openai.key')
                ])
                ->attach('file', file_get_contents($audioPath), basename($audioPath))
                ->post('https://api.openai.com/v1/audio/transcriptions', $params);

            if ($response->successful()) {
                return trim($response->body());
            }

            Log::error('OpenAI Whisper API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI Whisper exception', ['error' => $e->getMessage()]);
            return null;
        }
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
