<?php

namespace Database\Seeders;

use App\Models\DocumentUploadLog;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Contoh riwayat dokumen (halaman History) untuk pengembangan lokal.
 *
 * Tidak dipanggil dari DatabaseSeeder. Jalankan manual:
 *   php artisan db:seed --class=DocumentUploadLogSeeder
 *
 * Baris contoh ditandai document_id = 'contoh', jadi menjalankan ulang seeder ini menggantinya, bukan menambah.
 */
class DocumentUploadLogSeeder extends Seeder
{
    private const MARKER = 'contoh';

    private const TOTAL = 45;

    public function run(): void
    {
        DocumentUploadLog::where('document_id', self::MARKER)->delete();

        $programs = Program::query()->orderBy('id')->get(['id', 'title']);
        if ($programs->isEmpty()) {
            $this->command?->warn('Belum ada program. Jalankan ScmDataSeeder dulu.');

            return;
        }

        // Urutan acak tetap sama di setiap mesin
        mt_srand(20261124);

        $uploaders = ['Dewi Lestari', 'Siti Rahmawati', 'Rian Hidayat', 'Hendra Wijaya', 'Budi Santoso'];
        $now = Carbon::now('UTC');

        // Waktu dibuat dulu lalu diurutkan, supaya urutan id sama dengan urutan waktu seperti data asli.
        // Jam 01–10 UTC = 08.00–17.00 WIB (jam kerja).
        $times = collect(range(1, self::TOTAL))
            ->map(function () use ($now) {
                $time = $now->copy()->subDays(mt_rand(0, 29))->setTime(mt_rand(1, 9), mt_rand(0, 59), mt_rand(0, 59));

                return $time->greaterThan($now) ? $time->subDay() : $time;
            })
            ->sort()
            ->values();

        for ($i = 0; $i < self::TOTAL; $i++) {
            $program = $programs[mt_rand(0, $programs->count() - 1)];
            $source = $this->pick(['auto' => 60, 'manual' => 25, 'whms' => 15]);
            $type = $source === 'whms' ? 'mou' : $this->pick(['invoice' => 50, 'faktur_pajak' => 40, 'mou' => 10]);
            $status = $this->pick(['matched' => 55, 'duplicate' => 12, 'review' => 15, 'failed' => 10, 'skipped' => 8]);
            if ($source !== 'auto' && in_array($status, ['review', 'skipped'], true)) {
                // Unggah manual / WHMS langsung ke program, jadi tidak ada tahap "perlu dipilih"
                $status = 'matched';
            }
            // Pastikan setiap jenis hasil muncul beberapa kali
            if ($i % 8 === 3) {
                [$source, $status] = ['auto', 'review'];
            } elseif ($i % 15 === 7) {
                [$source, $status] = ['auto', 'skipped'];
            }
            if ($source === 'whms') {
                $type = 'mou';
            }

            $seq = 1000 + $i * 7;
            $invoiceNo = sprintf('INV/%s/2025/%04d', $this->supplierCode($program->title), $seq);
            $fakturNo = sprintf('010.002-25.%08d', 37289100 + $i * 13);
            $number = match ($type) {
                'invoice' => $invoiceNo,
                'faktur_pajak' => $fakturNo,
                default => sprintf('DO/SCM/2026/%03d', $i + 1),
            };

            $createdAt = $times[$i];

            $log = new DocumentUploadLog([
                'program_id' => in_array($status, ['matched', 'duplicate'], true) || $source !== 'auto' ? $program->id : null,
                'document_id' => self::MARKER,
                'file_name' => $this->fileName($type, $number, $i),
                'document_type' => $status === 'failed' && mt_rand(0, 1) ? null : $type,
                'source' => $source,
                'status' => $status,
                'reason' => $status === 'matched' ? $this->reason($source, $type, $number) : null,
                'message' => $this->message($status),
                'number' => in_array($status, ['matched', 'duplicate', 'review'], true) ? $number : null,
                'uploaded_by' => $source === 'whms' ? 'Petugas Gudang (WHMS)' : $uploaders[mt_rand(0, count($uploaders) - 1)],
            ]);
            $log->created_at = $createdAt;
            $log->updated_at = $createdAt;
            $log->save();
        }

        $this->command?->info(self::TOTAL.' contoh riwayat dokumen dibuat.');
    }

    /** Pilih kunci berdasarkan bobot, mis. ['a' => 70, 'b' => 30]. */
    private function pick(array $weights): string
    {
        $roll = mt_rand(1, array_sum($weights));
        foreach ($weights as $key => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return $key;
            }
        }

        return array_key_first($weights);
    }

    private function supplierCode(string $title): string
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($title));

        return substr($letters ?: 'SCM', 0, 3);
    }

    private function fileName(string $type, string $number, int $i): string
    {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $number));
        $variants = match ($type) {
            'invoice' => ["invoice-{$slug}.pdf", "scan_invoice_{$i}.jpg", "INV {$number}.pdf"],
            'faktur_pajak' => ["faktur-pajak-{$slug}.pdf", "FP_{$slug}.pdf", "efaktur_masukan_{$i}.pdf"],
            default => ["memo-do-{$slug}.pdf", "surat_jalan_{$i}.jpg"],
        };

        return $variants[mt_rand(0, count($variants) - 1)];
    }

    private function reason(string $source, string $type, string $number): string
    {
        if ($source === 'manual') {
            return 'Dipilih manual';
        }
        if ($source === 'whms') {
            return 'Dikirim dari WHMS';
        }

        return $type === 'faktur_pajak' ? 'Referensi SJ cocok' : 'No. SJ cocok dengan No. PO/SJ program';
    }

    private function message(string $status): ?string
    {
        return match ($status) {
            'duplicate' => 'Dokumen dengan nomor yang sama sudah ada di program ini.',
            'review' => ['Ada 2 program dengan No. SJ yang sama. Pilih programnya.', 'Nama penjual tidak cocok dengan supplier program mana pun. Pilih programnya.'][mt_rand(0, 1)],
            'failed' => ['AI belum bisa membaca dokumen ini: berkas buram. Pilih programnya.', 'Dokumen ini tidak dikenali sebagai invoice atau faktur pajak.', 'Berkas melebihi 15 MB.'][mt_rand(0, 2)],
            'skipped' => 'Dilewati oleh pengguna.',
            default => null,
        };
    }
}
