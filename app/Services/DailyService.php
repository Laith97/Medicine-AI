<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DailyService
{
    protected $apiKey;
    protected $domain;
    protected $baseUrl = 'https://api.daily.co/v1';

    public function __construct()
    {
        $this->apiKey = config('daily.api_key');
        $this->domain = config('daily.domain');
    }

    /**
     * Create a video room
     */
    public function createRoom($roomName, $expiresInMinutes = 60)
    {
        try {
            $response = Http::timeout(30)
                ->withOptions([
                    // Force IPv4 to avoid hanging on IPv6 (AAAA) DNS lookups
                    'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                ])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->baseUrl . '/rooms', [
                    'name' => $roomName,
                    'properties' => [
                        'exp' => time() + ($expiresInMinutes * 60),
                        'max_participants' => 2,
                        'enable_screenshare' => true,
                        'enable_chat' => false,
                        'enable_knocking' => false,
                        'start_video_off' => false,
                        'start_audio_off' => false,
                    ]
                ]);

            if ($response->failed()) {
                // If a room with this name already exists on Daily.co, we can
                // safely reuse it instead of treating this as a fatal error.
                if (str_contains($response->body(), 'already exists')) {
                    return $this->getRoom($roomName);
                }
                throw new \Exception('Daily.co API error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Daily.co createRoom error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get room details
     */
    public function getRoom($roomName)
    {
        $response = Http::withOptions([
            // Force IPv4 to avoid hanging on IPv6 (AAAA) DNS lookups
            'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
        ])->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($this->baseUrl . '/rooms/' . $roomName);

        return $response->json();
    }

    /**
     * Delete a room
     */
    public function deleteRoom($roomName)
    {
        $response = Http::withOptions([
            // Force IPv4 to avoid hanging on IPv6 (AAAA) DNS lookups
            'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
        ])->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->delete($this->baseUrl . '/rooms/' . $roomName);

        return $response->json();
    }

    /**
     * Create a meeting token for a participant
     */
    public function createMeetingToken($roomName, $userName, $isOwner = false)
    {
        try {
            $response = Http::timeout(30)
                ->retry(2, 100)
                ->withOptions([
                    // Force IPv4 to avoid hanging on IPv6 (AAAA) DNS lookups
                    'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                ])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->baseUrl . '/meeting-tokens', [
                    'properties' => [
                        'room_name' => $roomName,
                        'user_name' => $userName,
                        'is_owner' => $isOwner,
                        'enable_screenshare' => true,
                        'start_video_off' => false,
                        'start_audio_off' => false,
                    ]
                ]);

            if ($response->failed()) {
                \Log::error('Daily.co API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Daily.co API error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Daily.co createMeetingToken error: ' . $e->getMessage());
            throw $e;
        }
    }
}
