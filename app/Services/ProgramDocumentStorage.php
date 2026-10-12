<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Penyimpanan berkas dokumen program (Invoice, Faktur Pajak, Memo/DO).
 *
 * Berkas baru disimpan ke S3 (SeaweedFS) dengan key berawalan dokumen_program/. Jika S3 belum
 * dikonfigurasi atau unggahan gagal, berkas disimpan di public/uploads/documents seperti sebelumnya,
 * jadi unggahan tidak pernah gagal karena S3. Lokasi berkas dibedakan dari awalan file_path.
 */
class ProgramDocumentStorage
{
    public const S3_PREFIX = 'dokumen_program/';
    public const LOCAL_DIR = 'uploads/documents';

    /**
     * @return array{path: string, size: string, storage: string}
     */
    public static function store(UploadedFile $file, string $programId): array
    {
        $extension = preg_replace('/[^a-z0-9]/', '', strtolower($file->getClientOriginalExtension())) ?: 'pdf';
        $storedName = 'doc_' . time() . '_' . Str::random(8) . '.' . $extension;
        $size = self::formatSize((int) $file->getSize());

        $s3 = new SeaweedStorageService();
        if ($s3->isConfigured()) {
            $key = self::s3Key($programId, $storedName);
            try {
                $result = $s3->putObject($key, $file->getContent(), $file->getMimeType() ?: 'application/octet-stream');
                if ($result['success']) {
                    return ['path' => $key, 'size' => $size, 'storage' => 's3'];
                }
                Log::warning("Upload dokumen ke S3 gagal, disimpan lokal: {$result['error']}");
            } catch (\Throwable $e) {
                Log::warning('Upload dokumen ke S3 gagal, disimpan lokal: ' . $e->getMessage());
            }
        }

        $file->move(public_path(self::LOCAL_DIR), $storedName);

        return ['path' => self::LOCAL_DIR . '/' . $storedName, 'size' => $size, 'storage' => 'local'];
    }

    public static function s3Key(string $programId, string $storedName): string
    {
        return self::S3_PREFIX . preg_replace('/[^A-Za-z0-9_-]/', '_', $programId) . '/' . $storedName;
    }

    public static function isInS3(?string $path): bool
    {
        return str_starts_with((string) $path, self::S3_PREFIX);
    }

    /**
     * Isi berkas beserta mime, dari S3 atau folder lokal. Null jika berkas tidak ditemukan.
     *
     * @return array{content: string, mime: string}|null
     */
    public static function read(?string $path): ?array
    {
        if (!$path) {
            return null;
        }

        if (self::isInS3($path)) {
            return (new SeaweedStorageService())->getObject($path);
        }

        $localPath = public_path($path);
        if (!is_file($localPath)) {
            return null;
        }

        return [
            'content' => file_get_contents($localPath),
            'mime' => mime_content_type($localPath) ?: 'application/octet-stream',
        ];
    }

    public static function delete(?string $path): void
    {
        if (!$path) {
            return;
        }

        if (self::isInS3($path)) {
            (new SeaweedStorageService())->deleteObject($path);
            return;
        }

        if (is_file(public_path($path))) {
            @unlink(public_path($path));
        }
    }

    public static function formatSize(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : round($bytes / 1024, 1) . ' KB';
    }
}
