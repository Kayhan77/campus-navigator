<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SupabaseStorageService
{
    public function uploadImage(UploadedFile $file, string $directory): string
    {
        $path = $this->buildPath($directory, $file);
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';

        /** @var Response $uploadResponse */
        $uploadResponse = $this->client()->withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceKey(),
            'apikey' => $this->serviceKey(),
            'x-upsert' => 'true',
            'Content-Type' => $mimeType,
        ])->withBody(
            file_get_contents($file->getRealPath()),
            $mimeType
        )->post($this->objectUrl($path));

        if ($uploadResponse->status() === 404 && $this->isBucketMissing($uploadResponse)) {
            $this->ensureBucketExists();

            /** @var Response $uploadResponse */
            $uploadResponse = $this->client()->withHeaders([
                'Authorization' => 'Bearer ' . $this->serviceKey(),
                'apikey' => $this->serviceKey(),
                'x-upsert' => 'true',
                'Content-Type' => $mimeType,
            ])->withBody(
                file_get_contents($file->getRealPath()),
                $mimeType
            )->post($this->objectUrl($path));
        }

        if (! $uploadResponse->successful()) {
            throw new RuntimeException($this->formatError('Supabase upload failed', $uploadResponse));
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        /** @var Response $response */
        $response = $this->client()->withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceKey(),
            'apikey' => $this->serviceKey(),
        ])->delete($this->objectUrl($path));

        // Ignore not-found, fail hard on all other errors.
        if (! $response->successful() && $response->status() !== 404) {
            throw new RuntimeException($this->formatError('Supabase delete failed', $response));
        }
    }

    public static function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $supabaseUrl = rtrim((string) env('SUPABASE_URL'), '/');
        $bucket = (string) env('SUPABASE_STORAGE_BUCKET', 'Campus Navigator images');

        if ($supabaseUrl === '' || $bucket === '') {
            return null;
        }

        return sprintf(
            '%s/storage/v1/object/public/%s/%s',
            $supabaseUrl,
            rawurlencode($bucket),
            self::encodePath($path)
        );
    }

    private function ensureBucketExists(): void
    {
        /** @var Response $response */
        $response = $this->client()->withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceKey(),
            'apikey' => $this->serviceKey(),
        ])->post($this->bucketUrl(), [
            'id' => $this->bucket(),
            'name' => $this->bucket(),
            'public' => true,
        ]);

        if (! $response->successful() && $response->status() !== 409) {
            throw new RuntimeException($this->formatError('Supabase bucket creation failed', $response));
        }
    }

    private function buildPath(string $directory, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';

        return trim($directory, '/') . '/' . now()->format('Ymd_His') . '_' . Str::random(10) . '.' . strtolower($extension);
    }

    private function client(): PendingRequest
    {
        $client = Http::acceptJson();

        if (app()->environment('local')) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    private function objectUrl(string $path): string
    {
        return sprintf(
            '%s/storage/v1/object/%s/%s',
            $this->supabaseUrl(),
            rawurlencode($this->bucket()),
            self::encodePath($path)
        );
    }

    private function bucketUrl(): string
    {
        return $this->supabaseUrl() . '/storage/v1/bucket';
    }

    private function supabaseUrl(): string
    {
        $url = rtrim((string) env('SUPABASE_URL'), '/');

        if ($url === '') {
            throw new RuntimeException('SUPABASE_URL is missing.');
        }

        return $url;
    }

    private function serviceKey(): string
    {
        $key = (string) env('SUPABASE_SERVICE_ROLE_KEY');

        if ($key === '') {
            throw new RuntimeException('SUPABASE_SERVICE_ROLE_KEY is missing.');
        }

        return $key;
    }

    private function bucket(): string
    {
        return (string) env('SUPABASE_STORAGE_BUCKET', 'Campus Navigator images');
    }

    private function isBucketMissing(Response $response): bool
    {
        return str_contains(
            strtolower((string) data_get($response->json(), 'message', '')),
            'bucket not found'
        );
    }

    private static function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', trim($path, '/'))));
    }

    private function formatError(string $prefix, Response $response): string
    {
        $details = $response->json();

        if (! is_array($details) || $details === []) {
            $details = ['body' => $response->body()];
        }

        return sprintf('%s: %s', $prefix, json_encode($details));
    }
}
