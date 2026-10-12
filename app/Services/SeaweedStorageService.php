<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SeaweedStorageService
{
    protected string $accessKey;
    protected string $secretKey;
    protected string $region;
    protected string $bucket;
    protected string $endpoint;
    protected string $publicUrl;
    protected bool $usePathStyle;
    protected ?string $caBundle;

    public function __construct()
    {
        $this->accessKey = env('AWS_ACCESS_KEY_ID', 'seaweedfs-its');
        $this->secretKey = env('AWS_SECRET_ACCESS_KEY', 'xK9mP2vQ8nR4tY6wZ1cB3dF5gH7jL0oS9aU2eI4kM6n=');
        $this->region = env('AWS_DEFAULT_REGION', 'us-east-1');
        $this->bucket = env('AWS_BUCKET', 'SCM');
        $this->endpoint = rtrim(env('AWS_ENDPOINT', 'https://storage.completeselular.com'), '/');
        $this->publicUrl = rtrim(env('AWS_URL', 'https://storage.completeselular.com/Arsip SCM'), '/');
        $this->usePathStyle = filter_var(env('AWS_USE_PATH_STYLE_ENDPOINT', true), FILTER_VALIDATE_BOOLEAN);
        // Opsional: berkas CA untuk verifikasi TLS bila PHP tidak punya CA bundle (mis. Herd di Windows)
        $this->caBundle = env('AWS_CA_BUNDLE') ?: null;
    }

    /**
     * Upload raw import file to SeaweedFS / S3 storage
     *
     * @param UploadedFile|string $file UploadedFile instance or raw content string
     * @param string $originalName Original file name
     * @param string $folder Subfolder in S3 (e.g. 'mentahan_excel')
     * @return array
     */
    public function uploadRawFile($file, string $originalName = '', string $folder = 'mentahan_excel'): array
    {
        if ($file instanceof UploadedFile) {
            $originalName = $originalName ?: $file->getClientOriginalName();
            $content = file_get_contents($file->getRealPath());
            $mimeType = $file->getMimeType() ?: 'application/octet-stream';
            $sizeBytes = $file->getSize();
        } else {
            $content = (string) $file;
            $originalName = $originalName ?: ('import_' . date('Ymd_His') . '.xlsx');
            $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $sizeBytes = strlen($content);
        }

        $formattedSize = $sizeBytes >= 1048576
            ? round($sizeBytes / 1048576, 2) . ' MB'
            : round($sizeBytes / 1024, 1) . ' KB';

        // Safe unique file key
        $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $fileKey = trim($folder, '/') . '/' . date('Ymd_His') . '_' . $cleanName;

        // 1. Save local backup copy in public/uploads/mentahan_excel/
        $localDir = public_path('uploads/mentahan_excel');
        if (!file_exists($localDir)) {
            @mkdir($localDir, 0777, true);
        }
        $localFilePath = $localDir . '/' . basename($fileKey);
        @file_put_contents($localFilePath, $content);
        $localUrl = url('uploads/mentahan_excel/' . basename($fileKey));

        // 2. Upload to SeaweedFS S3
        $s3Url = null;
        $uploadSuccess = false;
        $errorMessage = null;

        try {
            $s3Result = $this->putObject($fileKey, $content, $mimeType);
            if ($s3Result['success']) {
                $uploadSuccess = true;
                $s3Url = $s3Result['url'];
            } else {
                $errorMessage = $s3Result['error'] ?? 'Gagal upload ke SeaweedFS';
            }
        } catch (\Throwable $e) {
            Log::warning('SeaweedFS upload error: ' . $e->getMessage());
            $errorMessage = $e->getMessage();
        }

        // Final accessible URL: S3 URL if success, otherwise local backup URL
        $finalUrl = ($uploadSuccess && $s3Url) ? $s3Url : $localUrl;

        return [
            'success' => true,
            'storage' => $uploadSuccess ? 'seaweedfs' : 'local_backup',
            'file_name' => $originalName,
            'file_key' => $fileKey,
            'file_size' => $formattedSize,
            'file_url' => $finalUrl,
            's3_url' => $s3Url,
            'local_url' => $localUrl,
            'warning' => $uploadSuccess ? null : "Disimpan di cadangan lokal: {$errorMessage}",
            'uploaded_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Apakah endpoint, kredensial, dan bucket S3 sudah diisi. Kalau belum, unggahan langsung disimpan lokal.
     */
    public function isConfigured(): bool
    {
        return $this->endpoint !== ''
            && $this->accessKey !== ''
            && $this->secretKey !== ''
            && trim($this->bucket, '/') !== '';
    }

    /**
     * Send PUT request to S3 using AWS SigV4
     */
    public function putObject(string $key, string $content, string $mimeType): array
    {
        $response = $this->signedRequest('PUT', $key, $content, $mimeType, 12);

        if ($response->successful()) {
            $url = $this->publicUrl . '/' . ltrim($key, '/');
            return [
                'success' => true,
                'url' => $url,
                'status' => $response->status()
            ];
        }

        return [
            'success' => false,
            'error' => 'HTTP ' . $response->status() . ': ' . $response->body()
        ];
    }

    /**
     * Download object content from SeaweedFS S3
     */
    public function getObject(string $key): ?array
    {
        try {
            $response = $this->signedRequest('GET', $key, '', null, 10);

            if ($response->successful()) {
                return [
                    'content' => $response->body(),
                    'mime' => $response->header('Content-Type') ?: 'application/octet-stream'
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('SeaweedFS getObject error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Hapus objek dari SeaweedFS S3
     */
    public function deleteObject(string $key): bool
    {
        try {
            return $this->signedRequest('DELETE', $key, '', null, 10)->successful();
        } catch (\Throwable $e) {
            Log::warning('SeaweedFS deleteObject error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Request ke satu objek di bucket, ditandatangani dengan AWS SigV4 (path-style)
     */
    private function signedRequest(string $method, string $key, string $content, ?string $mimeType, int $timeout): Response
    {
        $host = parse_url($this->endpoint, PHP_URL_HOST);
        $scheme = parse_url($this->endpoint, PHP_URL_SCHEME) ?: 'https';
        $port = parse_url($this->endpoint, PHP_URL_PORT);
        $portSuffix = $port ? (':' . $port) : '';

        // S3 standard requires lowercase bucket name
        $bucket = strtolower(trim($this->bucket, '/'));
        $uri = '/' . $bucket . '/' . ltrim($key, '/');
        $fullUrl = "{$scheme}://{$host}{$portSuffix}{$uri}";

        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $payloadHash = hash('sha256', $content);

        $canonicalHeaders = "host:" . strtolower((string) $host) . ($port ? ":$port" : '') . "\n"
            . "x-amz-content-sha256:" . $payloadHash . "\n"
            . "x-amz-date:" . $amzDate . "\n";
        $signedHeaders = "host;x-amz-content-sha256;x-amz-date";

        $canonicalRequest = "{$method}\n{$uri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        $credentialScope = "{$dateStamp}/{$this->region}/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $headers = [
            'Host' => $host . ($port ? ":$port" : ''),
            'x-amz-date' => $amzDate,
            'x-amz-content-sha256' => $payloadHash,
            'Authorization' => "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}",
        ];

        $request = Http::timeout($timeout)->withOptions(['verify' => $this->caBundle ?? true]);
        if ($method === 'PUT') {
            // withBody sudah mengirim Content-Type; menambahkannya lagi di header membuat nilainya dobel
            $request = $request->withBody($content, $mimeType);
        }

        return $request->withHeaders($headers)->send($method, $fullUrl);
    }
}
