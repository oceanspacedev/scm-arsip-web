<?php

namespace App\Console\Commands;

use App\Models\ProgramDocument;
use App\Services\ProgramDocumentStorage;
use App\Services\SeaweedStorageService;
use Illuminate\Console\Command;

class MoveDocumentsToS3 extends Command
{
    protected $signature = 'arsip:documents-to-s3
        {--dry-run : Hanya tampilkan dokumen yang akan dipindah}
        {--delete-local : Hapus berkas lokal setelah berhasil diunggah}';

    protected $description = 'Pindahkan berkas dokumen program yang masih tersimpan lokal ke S3 (SeaweedFS)';

    public function handle(): int
    {
        $s3 = new SeaweedStorageService();
        $dryRun = (bool) $this->option('dry-run');

        if (!$dryRun && !$s3->isConfigured()) {
            $this->error('S3 belum dikonfigurasi: isi AWS_ENDPOINT, AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, dan AWS_BUCKET di .env.');
            return self::FAILURE;
        }

        $documents = ProgramDocument::where('file_path', 'like', ProgramDocumentStorage::LOCAL_DIR . '/%')->get();
        $moved = 0;

        foreach ($documents as $document) {
            $localPath = public_path($document->file_path);
            if (!is_file($localPath)) {
                $this->warn("Lewati {$document->id}: berkas lokal tidak ditemukan ({$document->file_path})");
                continue;
            }

            $key = ProgramDocumentStorage::s3Key((string) $document->program_id, basename($document->file_path));
            if ($dryRun) {
                $this->line("{$document->file_path} -> {$key}");
                continue;
            }

            try {
                $result = $s3->putObject($key, file_get_contents($localPath), mime_content_type($localPath) ?: 'application/octet-stream');
            } catch (\Throwable $e) {
                $result = ['success' => false, 'error' => $e->getMessage()];
            }

            if (!$result['success']) {
                $this->error("Gagal memindah {$document->id}: {$result['error']}");
                continue;
            }

            $document->update(['file_path' => $key]);
            if ($this->option('delete-local')) {
                @unlink($localPath);
            }
            $moved++;
            $this->info("Dipindah: {$document->file_name} -> {$key}");
        }

        $this->info($dryRun
            ? "{$documents->count()} dokumen lokal akan dipindah."
            : "{$moved} dari {$documents->count()} dokumen lokal dipindah ke S3.");

        return self::SUCCESS;
    }
}
