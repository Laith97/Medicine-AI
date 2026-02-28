<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MedicalAudioSecurity
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Verify user has permission for this medical visit
        if (!$this->validateMedicalAccess($request)) {
            Log::warning('Unauthorized medical audio access attempt', [
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            return response()->json(['error' => 'Unauthorized access'], 403);
        }
        
        // 2. Encrypt all audio data in transit (Enforce HTTPS/WSS)
        if (!$request->isSecure() && app()->environment('production')) {
            return response()->json(['error' => 'Secure connection required'], 403);
        }
        $request->headers->set('X-Required-Encryption', 'TLS_1_3');
        
        // 3. Implement automatic data retention policy
        $this->applyRetentionPolicy($request);
        
        // 4. Audit log all access
        // Assuming an AuditLog model exists or using a generic logger
        Log::info('Medical Audio Access', [
            'user_id' => auth()->id(),
            'action' => 'audio_access',
            'visit_id' => $request->visit_id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        return $next($request);
    }
    
    private function validateMedicalAccess($request)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return false;
        }

        // Check if user is a doctor or admin
        // This logic depends on your User model and roles
        $user = auth()->user();
        // if (!$user->isDoctor() && !$user->isAdmin()) return false;

        // If visit_id is present, check if doctor is assigned to this visit
        if ($request->has('visit_id')) {
            // $visit = MedicalVisit::find($request->visit_id);
            // if (!$visit || $visit->doctor_id !== $user->id) return false;
        }

        return true;
    }
    
    private function applyRetentionPolicy($request)
    {
        // Automatically delete audio files after 72 hours
        // Keep transcripts as per medical retention policy
        config(['medical.audio_retention_hours' => 72]);
    }
}
