<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kiosk;
use App\Models\KioskSession;
use App\Services\AuditLoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class KioskController extends Controller
{
    /**
     * Register or update kiosk status
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'serial_number' => 'required|string|unique:kiosks,serial_number',
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'configuration' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $kiosk = Kiosk::updateOrCreate(
                ['serial_number' => $request->serial_number],
                [
                    'name' => $request->name,
                    'location' => $request->location,
                    'configuration' => $request->configuration,
                    'status' => 'active',
                    'last_ping' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Kiosk registered successfully',
                'data' => $kiosk,
            ]);
        } catch (\Exception $e) {
            Log::error('Kiosk registration failed', [
                'serial_number' => $request->serial_number,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to register kiosk',
            ], 500);
        }
    }

    /**
     * Update kiosk ping/status
     */
    public function ping(Request $request, Kiosk $kiosk): JsonResponse
    {
        try {
            $kiosk->updateLastPing();

            return response()->json([
                'success' => true,
                'message' => 'Kiosk ping updated',
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Kiosk ping update failed', [
                'kiosk_id' => $kiosk->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update kiosk ping',
            ], 500);
        }
    }

    /**
     * Start a new kiosk session
     */
    public function startSession(Request $request, Kiosk $kiosk): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // End any existing active sessions for this kiosk
            KioskSession::where('kiosk_id', $kiosk->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'abandoned',
                    'end_time' => now(),
                ]);

            $session = KioskSession::create([
                'kiosk_id' => $kiosk->id,
                'status' => 'active',
                'session_data' => $request->session_data,
            ]);

            // Log session start
            AuditLoggingService::logKioskSessionStarted($kiosk->id, $session->session_id, [
                'kiosk_name' => $kiosk->name,
                'kiosk_location' => $kiosk->location,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session started successfully',
                'data' => $session,
            ]);
        } catch (\Exception $e) {
            Log::error('Session start failed', [
                'kiosk_id' => $kiosk->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start session',
            ], 500);
        }
    }

    /**
     * End a kiosk session
     */
    public function endSession(Request $request, KioskSession $session): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:completed,abandoned,error',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $status = $request->status ?? 'completed';
            $session->end($status);

            // Log session end
            AuditLoggingService::logKioskSessionEnded($session->kiosk_id, $session->session_id, [
                'end_status' => $status,
                'session_duration_minutes' => $session->getDurationInMinutes(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session ended successfully',
                'data' => $session,
            ]);
        } catch (\Exception $e) {
            Log::error('Session end failed', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);

            // Log failed session end
            AuditLoggingService::logKioskSecurityEvent('session_end_failed', $session->session_id, [
                'error' => $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to end session',
            ], 500);
        }
    }

    /**
     * Get kiosk status
     */
    public function status(Kiosk $kiosk): JsonResponse
    {
        try {
            $activeSession = $kiosk->sessions()->active()->first();
            $isOnline = $kiosk->isOnline();

            return response()->json([
                'success' => true,
                'data' => [
                    'kiosk' => $kiosk,
                    'is_online' => $isOnline,
                    'active_session' => $activeSession,
                    'total_sessions_today' => $kiosk->sessions()
                        ->whereDate('start_time', today())
                        ->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Kiosk status check failed', [
                'kiosk_id' => $kiosk->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get kiosk status',
            ], 500);
        }
    }

    /**
     * Update kiosk configuration
     */
    public function updateConfiguration(Request $request, Kiosk $kiosk): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'configuration' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $kiosk->update([
                'configuration' => array_merge(
                    $kiosk->configuration ?? [],
                    $request->configuration
                ),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully',
                'data' => $kiosk,
            ]);
        } catch (\Exception $e) {
            Log::error('Configuration update failed', [
                'kiosk_id' => $kiosk->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update configuration',
            ], 500);
        }
    }

    /**
     * Send command to kiosk
     */
    public function sendCommand(Request $request, Kiosk $kiosk): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'command' => 'required|in:restart,shutdown,update,diagnostics,status',
            'parameters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Store command in kiosk's pending commands (you might want to create a commands table)
            $command = [
                'command' => $request->command,
                'parameters' => $request->parameters ?? [],
                'issued_at' => now(),
                'issued_by' => auth('api')->id(),
            ];

            // For now, store in configuration as pending_commands
            $config = $kiosk->configuration ?? [];
            $pendingCommands = $config['pending_commands'] ?? [];
            $pendingCommands[] = $command;
            $config['pending_commands'] = $pendingCommands;

            $kiosk->update(['configuration' => $config]);

            // Log the command
            AuditLoggingService::logKioskSecurityEvent('command_issued', null, [
                'kiosk_id' => $kiosk->id,
                'command' => $request->command,
                'parameters' => $request->parameters,
                'issued_by' => auth('api')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Command sent to kiosk',
                'data' => [
                    'command' => $request->command,
                    'issued_at' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Command send failed', [
                'kiosk_id' => $kiosk->id,
                'command' => $request->command,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send command',
            ], 500);
        }
    }

    /**
     * Check for pending commands
     */
    public function getPendingCommands(Kiosk $kiosk): JsonResponse
    {
        try {
            $config = $kiosk->configuration ?? [];
            $pendingCommands = $config['pending_commands'] ?? [];

            return response()->json([
                'success' => true,
                'data' => [
                    'commands' => $pendingCommands,
                    'count' => count($pendingCommands),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get pending commands', [
                'kiosk_id' => $kiosk->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get pending commands',
            ], 500);
        }
    }

    /**
     * Acknowledge command execution
     */
    public function acknowledgeCommand(Request $request, Kiosk $kiosk): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'command_index' => 'required|integer|min:0',
            'result' => 'required|in:success,failed',
            'output' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $config = $kiosk->configuration ?? [];
            $pendingCommands = $config['pending_commands'] ?? [];

            if (!isset($pendingCommands[$request->command_index])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Command not found',
                ], 404);
            }

            $command = $pendingCommands[$request->command_index];
            $command['executed_at'] = now();
            $command['result'] = $request->result;
            $command['output'] = $request->output;

            // Move to executed commands
            $executedCommands = $config['executed_commands'] ?? [];
            $executedCommands[] = $command;
            $config['executed_commands'] = $executedCommands;

            // Remove from pending
            unset($pendingCommands[$request->command_index]);
            $config['pending_commands'] = array_values($pendingCommands);

            $kiosk->update(['configuration' => $config]);

            return response()->json([
                'success' => true,
                'message' => 'Command acknowledged',
            ]);
        } catch (\Exception $e) {
            Log::error('Command acknowledgment failed', [
                'kiosk_id' => $kiosk->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to acknowledge command',
            ], 500);
        }
    }

    /**
     * Get software update information
     */
    public function getSoftwareUpdate(Kiosk $kiosk): JsonResponse
    {
        try {
            // Check if there's a pending software update
            $config = $kiosk->configuration ?? [];
            $currentVersion = $config['software_version'] ?? '1.0.0';
            $latestVersion = config('kiosk.software_version', '1.0.0');

            $updateAvailable = version_compare($latestVersion, $currentVersion, '>');

            return response()->json([
                'success' => true,
                'data' => [
                    'current_version' => $currentVersion,
                    'latest_version' => $latestVersion,
                    'update_available' => $updateAvailable,
                    'update_url' => $updateAvailable ? route('api.kiosk.software.download', $kiosk) : null,
                    'changelog' => $this->getChangelog($currentVersion, $latestVersion),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Software update check failed', [
                'kiosk_id' => $kiosk->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check for software updates',
            ], 500);
        }
    }

    /**
     * Download software update
     */
    public function downloadSoftwareUpdate(Kiosk $kiosk)
    {
        try {
            $updateFile = storage_path('app/kiosk/updates/kiosk-update.zip');

            if (!file_exists($updateFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Update file not found',
                ], 404);
            }

            return response()->download($updateFile, 'kiosk-update.zip', [
                'Content-Type' => 'application/zip',
            ]);
        } catch (\Exception $e) {
            Log::error('Software download failed', [
                'kiosk_id' => $kiosk->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to download software update',
            ], 500);
        }
    }

    /**
     * Report software update status
     */
    public function reportUpdateStatus(Request $request, Kiosk $kiosk): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'version' => 'required|string',
            'status' => 'required|in:downloading,installing,completed,failed',
            'progress' => 'nullable|integer|min:0|max:100',
            'error_message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $config = $kiosk->configuration ?? [];
            $config['software_version'] = $request->version;
            $config['update_status'] = [
                'status' => $request->status,
                'progress' => $request->progress ?? 0,
                'updated_at' => now(),
                'error_message' => $request->error_message,
            ];

            $kiosk->update(['configuration' => $config]);

            return response()->json([
                'success' => true,
                'message' => 'Update status reported',
            ]);
        } catch (\Exception $e) {
            Log::error('Update status report failed', [
                'kiosk_id' => $kiosk->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to report update status',
            ], 500);
        }
    }

    /**
     * Get changelog between versions
     */
    private function getChangelog(string $fromVersion, string $toVersion): array
    {
        // This would typically read from a changelog file or database
        return [
            '1.1.0' => [
                'Bug fixes for payment processing',
                'Improved accessibility features',
                'Enhanced security measures',
            ],
            '1.0.5' => [
                'Fixed kiosk session timeout issues',
                'Added QR code scanning support',
            ],
        ];
    }
}
