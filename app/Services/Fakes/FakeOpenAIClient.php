<?php

namespace App\Services\Fakes;

use App\Services\OpenAIClient;

class FakeOpenAIClient extends OpenAIClient
{
    public function __construct() {}

    public function transcribeAudioBinary(string $binary, string $filename = 'audio.webm', string $model = 'gpt-4o-mini-transcribe', array $params = []): ?string
    {
        return 'This is a fake transcription for '.$filename;
    }

    public function transcribeAudioWithHints(string $binary, string $filename, ?string $language = null, bool $diarization = false, array $extra = []): ?string
    {
        $prefix = $language ? "[$language] " : '';
        return $prefix.'Fake partial transcript for '.$filename;
    }

    public function postToOpenAI(string $endpoint, array $payload)
    {
        // Return a fake JSON response similar to OpenAI
        return new class {
            public function json($key = null) {
                $content = json_encode([
                    ['type' => 'symptom', 'data' => ['keyword' => 'pain'], 'confidence' => 0.9],
                ]);
                $resp = [
                    'choices' => [
                        [ 'message' => [ 'content' => $content ] ]
                    ]
                ];
                if ($key === null) return $resp;
                // Simple dot notation support for 'choices.0.message.content'
                if ($key === 'choices.0.message.content') return $resp['choices'][0]['message']['content'];
                return null;
            }
        };
    }
}
