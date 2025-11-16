<?php

namespace App\Services;

use App\Models\ClearinghouseAccount;
use App\Models\ClearinghouseSubmission;
use App\Models\ClearinghouseResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ClearinghouseApiClient
{
    protected ClearinghouseAccount $account;
    protected array $config;
    protected ?string $accessToken = null;
    protected ?string $sessionId = null;

    public function __construct(ClearinghouseAccount $account)
    {
        $this->account = $account;
        $this->config = $this->getProviderConfig($account->provider);
    }

    /**
     * Submit EDI content to clearinghouse
     */
    public function submitEDI(string $ediContent, array $metadata = []): array
    {
        try {
            $this->ensureAuthenticated();

            $response = Http::withToken($this->accessToken)
                ->withHeaders($this->getDefaultHeaders())
                ->timeout(30)
                ->post($this->config['endpoints']['submit'], [
                    'edi_content' => $ediContent,
                    'metadata' => $metadata,
                    'format' => 'x12',
                    'version' => '5010'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'batch_id' => $data['batch_id'] ?? null,
                    'tracking_id' => $data['tracking_id'] ?? null,
                    'status' => $data['status'] ?? 'submitted',
                    'response' => $data
                ];
            }

            Log::error('EDI submission failed', [
                'provider' => $this->account->provider,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Submission failed with status ' . $response->status(),
                'details' => $response->json() ?? $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('EDI submission exception', [
                'provider' => $this->account->provider,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception during submission: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check submission status
     */
    public function checkStatus(string $batchId): array
    {
        try {
            $this->ensureAuthenticated();

            $response = Http::withToken($this->accessToken)
                ->withHeaders($this->getDefaultHeaders())
                ->timeout(15)
                ->get($this->config['endpoints']['status'], [
                    'batch_id' => $batchId
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $data['status'] ?? 'unknown',
                    'responses' => $data['responses'] ?? [],
                    'last_updated' => $data['last_updated'] ?? null,
                    'response' => $data
                ];
            }

            return [
                'success' => false,
                'error' => 'Status check failed with status ' . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Status check exception', [
                'provider' => $this->account->provider,
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception during status check: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve responses for a submission
     */
    public function getResponses(string $batchId, string $responseType = null): array
    {
        try {
            $this->ensureAuthenticated();

            $params = ['batch_id' => $batchId];
            if ($responseType) {
                $params['type'] = $responseType;
            }

            $response = Http::withToken($this->accessToken)
                ->withHeaders($this->getDefaultHeaders())
                ->timeout(15)
                ->get($this->config['endpoints']['responses'], $params);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'responses' => $data['responses'] ?? [],
                    'response' => $data
                ];
            }

            return [
                'success' => false,
                'error' => 'Response retrieval failed with status ' . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Response retrieval exception', [
                'provider' => $this->account->provider,
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception during response retrieval: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Authenticate with the clearinghouse API
     */
    protected function authenticate(): bool
    {
        try {
            $credentials = $this->account->getDecryptedCredentials();

            $authData = [
                'username' => $credentials['username'] ?? '',
                'password' => $credentials['password'] ?? '',
                'client_id' => $credentials['client_id'] ?? '',
                'client_secret' => $credentials['client_secret'] ?? '',
            ];

            // Remove empty values
            $authData = array_filter($authData);

            $response = Http::timeout(15)
                ->post($this->config['endpoints']['auth'], $authData);

            if ($response->successful()) {
                $data = $response->json();

                $this->accessToken = $data['access_token'] ?? $data['token'] ?? null;
                $this->sessionId = $data['session_id'] ?? null;

                // Cache the token for reuse
                $cacheKey = "clearinghouse_token_{$this->account->id}";
                $expiresAt = now()->addMinutes($data['expires_in'] ?? 60);
                Cache::put($cacheKey, $this->accessToken, $expiresAt);

                $this->account->markAsUsed();

                return true;
            }

            Log::error('Authentication failed', [
                'provider' => $this->account->provider,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Authentication exception', [
                'provider' => $this->account->provider,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Ensure we have valid authentication
     */
    protected function ensureAuthenticated(): void
    {
        $cacheKey = "clearinghouse_token_{$this->account->id}";
        $cachedToken = Cache::get($cacheKey);

        if ($cachedToken) {
            $this->accessToken = $cachedToken;
            return;
        }

        if (!$this->authenticate()) {
            throw new \Exception("Failed to authenticate with {$this->account->provider}");
        }
    }

    /**
     * Get default headers for API requests
     */
    protected function getDefaultHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'Medicine-AI/1.0',
        ];

        if ($this->sessionId) {
            $headers['X-Session-ID'] = $this->sessionId;
        }

        return $headers;
    }

    /**
     * Get provider-specific configuration
     */
    protected function getProviderConfig(string $provider): array
    {
        $configs = [
            'availity' => [
                'endpoints' => [
                    'auth' => 'https://api.availity.com/auth/v1/token',
                    'submit' => 'https://api.availity.com/claims/v1/submit',
                    'status' => 'https://api.availity.com/claims/v1/status',
                    'responses' => 'https://api.availity.com/claims/v1/responses',
                ],
                'auth_type' => 'oauth2',
            ],
            'change_healthcare' => [
                'endpoints' => [
                    'auth' => 'https://api.changehealthcare.com/auth/token',
                    'submit' => 'https://api.changehealthcare.com/claims/submit',
                    'status' => 'https://api.changehealthcare.com/claims/status',
                    'responses' => 'https://api.changehealthcare.com/claims/responses',
                ],
                'auth_type' => 'oauth2',
            ],
            'trizetto' => [
                'endpoints' => [
                    'auth' => 'https://api.trizetto.com/oauth/token',
                    'submit' => 'https://api.trizetto.com/claims/v1/submissions',
                    'status' => 'https://api.trizetto.com/claims/v1/status',
                    'responses' => 'https://api.trizetto.com/claims/v1/responses',
                ],
                'auth_type' => 'oauth2',
            ],
            'default' => [
                'endpoints' => [
                    'auth' => env('CLEARINGHOUSE_AUTH_URL', 'https://api.clearinghouse.com/auth'),
                    'submit' => env('CLEARINGHOUSE_SUBMIT_URL', 'https://api.clearinghouse.com/submit'),
                    'status' => env('CLEARINGHOUSE_STATUS_URL', 'https://api.clearinghouse.com/status'),
                    'responses' => env('CLEARINGHOUSE_RESPONSES_URL', 'https://api.clearinghouse.com/responses'),
                ],
                'auth_type' => 'basic',
            ],
        ];

        return $configs[$provider] ?? $configs['default'];
    }

    /**
     * Test connection to clearinghouse
     */
    public function testConnection(): array
    {
        try {
            $this->ensureAuthenticated();

            $response = Http::withToken($this->accessToken)
                ->withHeaders($this->getDefaultHeaders())
                ->timeout(10)
                ->get($this->config['endpoints']['status'], [
                    'test' => true
                ]);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response_time' => $response->handlerStats()['total_time'] ?? null,
                'message' => $response->successful() ? 'Connection successful' : 'Connection failed'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Connection test failed'
            ];
        }
    }
}
