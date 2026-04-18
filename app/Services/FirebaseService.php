<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function sendNotification(?string $token, string $title, string $body, array $data = []): void
    {
        if (empty($token)) {
            return;
        }

        try {
            $projectId = $this->resolveProjectId();
            $accessToken = $this->resolveAccessToken();

            if (empty($projectId) || empty($accessToken)) {
                return;
            }

            $client = new Client(['timeout' => 10]);

            $response = $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => $this->normalizeData($data),
                    ],
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException('FCM request failed with status ' . $response->getStatusCode());
            }
        } catch (\Throwable $e) {
            // FCM errors must never break the main API flow.
            Log::warning('[FCM HTTP v1] Notification send failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveAccessToken(): ?string
    {
        $credentialsPath = $this->resolveCredentialsPath();

        if (! $credentialsPath || ! is_file($credentialsPath)) {
            return null;
        }

        $credentials = new ServiceAccountCredentials(self::FCM_SCOPE, $credentialsPath);
        $authToken = $credentials->fetchAuthToken(HttpHandlerFactory::build());

        return $authToken['access_token'] ?? null;
    }

    private function resolveProjectId(): ?string
    {
        $fromEnv = env('FIREBASE_PROJECT_ID');
        if (! empty($fromEnv)) {
            return $fromEnv;
        }

        $credentialsPath = $this->resolveCredentialsPath();
        if (! $credentialsPath || ! is_file($credentialsPath)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($credentialsPath), true);

        return $json['project_id'] ?? null;
    }

    private function resolveCredentialsPath(): ?string
    {
        return env('FIREBASE_CREDENTIALS')
            ?? env('GOOGLE_APPLICATION_CREDENTIALS')
            ?? null;
    }

    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $normalized[(string) $key] = is_scalar($value) || $value === null
                ? (string) $value
                : json_encode($value);
        }

        return $normalized;
    }
}
