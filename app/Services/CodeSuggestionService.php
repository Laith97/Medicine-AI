<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class CodeSuggestionService
{
    public function suggestCodes(string $clinicalText): array
    {
        // Validate input
        if (empty(trim($clinicalText))) {
            return ['error' => 'Clinical text cannot be empty'];
        }

        if (strlen($clinicalText) > 10000) {
            return ['error' => 'Clinical text too long (max 10000 characters)'];
        }

        // Sanitize input - remove potentially dangerous characters
        $sanitizedText = $this->sanitizeInput($clinicalText);

        try {
            $result = $this->executePythonScript($sanitizedText);
            return $result;
        } catch (\Exception $e) {
            Log::error('Code suggestion service error', [
                'error' => $e->getMessage(),
                'clinical_text_length' => strlen($clinicalText)
            ]);
            return ['error' => 'Failed to process code suggestions'];
        }
    }

    private function sanitizeInput(string $input): string
    {
        // Remove null bytes and other potentially dangerous characters
        $input = str_replace(["\0", "\r", "\n"], '', $input);

        // Limit length and trim
        return trim(substr($input, 0, 5000));
    }

    private function executePythonScript(string $clinicalText): array
    {
        $pythonScript = base_path('python/code_suggester.py');

        // Use proc_open for safer execution
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];

        $process = proc_open("python3 {$pythonScript}", $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new \Exception('Failed to start Python process');
        }

        // Send input to stdin
        fwrite($pipes[0], $clinicalText);
        fclose($pipes[0]);

        // Get output
        $output = stream_get_contents($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            Log::error('Python script execution failed', [
                'return_code' => $returnCode,
                'error_output' => $errorOutput,
                'output' => $output
            ]);
            throw new \Exception('Python script execution failed');
        }

        $result = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid JSON from Python script', [
                'output' => $output,
                'json_error' => json_last_error_msg()
            ]);
            throw new \Exception('Invalid response format from Python script');
        }

        return $result;
    }
}
