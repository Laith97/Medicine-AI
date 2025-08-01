<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsService
{
    protected $provider;
    protected $config;

    public function __construct()
    {
        $this->provider = config('sms.default_provider', 'log');
        $this->config = config('sms.providers.' . $this->provider, []);
    }

    /**
     * Send SMS message
     */
    public function send(string $to, string $message): bool
    {
        try {
            switch ($this->provider) {
                case 'twilio':
                    return $this->sendViaTwilio($to, $message);
                case 'nexmo':
                    return $this->sendViaNexmo($to, $message);
                case 'log':
                default:
                    return $this->sendViaLog($to, $message);
            }
        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage(), [
                'to' => $to,
                'message' => $message,
                'provider' => $this->provider
            ]);
            return false;
        }
    }

    /**
     * Send SMS via Twilio
     */
    protected function sendViaTwilio(string $to, string $message): bool
    {
        $accountSid = $this->config['account_sid'] ?? '';
        $authToken = $this->config['auth_token'] ?? '';
        $fromNumber = $this->config['from_number'] ?? '';

        if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
            Log::error('Twilio configuration missing');
            return false;
        }

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $fromNumber,
                'To' => $to,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            Log::info('SMS sent successfully via Twilio', ['to' => $to]);
            return true;
        }

        Log::error('Twilio SMS failed', [
            'to' => $to,
            'response' => $response->body()
        ]);
        return false;
    }

    /**
     * Send SMS via Nexmo/Vonage
     */
    protected function sendViaNexmo(string $to, string $message): bool
    {
        $apiKey = $this->config['api_key'] ?? '';
        $apiSecret = $this->config['api_secret'] ?? '';
        $fromNumber = $this->config['from_number'] ?? '';

        if (empty($apiKey) || empty($apiSecret) || empty($fromNumber)) {
            Log::error('Nexmo configuration missing');
            return false;
        }

        $response = Http::post('https://rest.nexmo.com/sms/json', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'from' => $fromNumber,
            'to' => $to,
            'text' => $message,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['messages'][0]['status']) && $data['messages'][0]['status'] === '0') {
                Log::info('SMS sent successfully via Nexmo', ['to' => $to]);
                return true;
            }
        }

        Log::error('Nexmo SMS failed', [
            'to' => $to,
            'response' => $response->body()
        ]);
        return false;
    }

    /**
     * Log SMS instead of sending (for development/testing)
     */
    protected function sendViaLog(string $to, string $message): bool
    {
        Log::info('SMS would be sent', [
            'to' => $to,
            'message' => $message,
            'provider' => 'log'
        ]);
        return true;
    }

    /**
     * Generate temporary password
     */
    public static function generateTempPassword(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';

        for ($i = 0; $i < 8; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $password;
    }
}
