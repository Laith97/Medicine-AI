<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.transcription', [
            'provider' => config('medical.transcription_provider'),
            'assemblyai_key' => config('medical.assemblyai.api_key'),
            'retention_hours' => config('medical.audio_retention_hours'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'transcription_provider' => 'required|in:google,assemblyai',
            'assemblyai_api_key' => 'nullable|string',
            'audio_retention_hours' => 'required|integer|min:1|max:720',
        ]);

        // Update .env file
        $this->updateEnvironmentFile([
            'MEDICAL_TRANSCRIPTION_PROVIDER' => $validated['transcription_provider'],
            'ASSEMBLYAI_API_KEY' => $validated['assemblyai_api_key'],
            'MEDICAL_AUDIO_RETENTION_HOURS' => $validated['audio_retention_hours'],
        ]);

        return back()->with('success', 'Settings updated successfully.');
    }

    protected function updateEnvironmentFile(array $data)
    {
        $path = base_path('.env');

        if (File::exists($path)) {
            $content = File::get($path);

            foreach ($data as $key => $value) {
                // If key exists, replace it
                if (preg_match("/^{$key}=.*/m", $content)) {
                    $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
                } else {
                    // If key doesn't exist, append it
                    $content .= "\n{$key}={$value}";
                }
            }

            File::put($path, $content);
        }
    }
}
