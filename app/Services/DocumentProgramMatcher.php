<?php

namespace App\Services;

use App\Models\Program;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Mencari program arsip untuk invoice / faktur pajak hasil bacaan AI.
 *
 * Dokumen hanya dilampirkan otomatis bila terkunci ke tepat satu program:
 * 1. Nomor: No. SJ yang tercantum di No. PO/SJ program (pada faktur pajak tertulis sebagai Referensi di
 *    pojok kiri bawah), No. Invoice yang sama, atau No. Faktur yang sama. Faktur tanpa Referensi boleh
 *    memakai DPP (dan PPN) yang sama, asal pihak/nama programnya mirip.
 * 2. Badan usaha: penjual DAN pembeli di dokumen sama dengan company & supplier program, karena No. SJ
 *    yang sama bisa dipakai badan usaha lain.
 * 3. Periode: bulan/tahun di deskripsi dokumen tidak bertentangan dengan nama program.
 * Bila beberapa program lolos, jenis tagihan dan nama program dipakai untuk memilih, dan hanya bila
 * pemenangnya jelas. Selain itu dikembalikan kandidat untuk dipilih.
 */
class DocumentProgramMatcher
{
    public const INVOICE = 'invoice';
    public const FAKTUR = 'faktur_pajak';

    private const MIN_KEY_LENGTH = 5;

    private const MIN_FAKTUR_DIGITS = 13;

    /** Selisih pembulatan rupiah yang masih dianggap nilai yang sama */
    private const AMOUNT_TOLERANCE = 1.0;

    /** Kemiripan pihak/nama program minimum agar DPP yang sama boleh jadi kunci */
    private const MIN_NAME_SIMILARITY_FOR_AMOUNT = 0.5;

    /** Kemiripan nama minimum agar penjual / pembeli dianggap badan usaha yang sama */
    private const MIN_PARTY_SIMILARITY = 0.5;

    /** Selisih skor minimum agar kandidat teratas boleh dipilih otomatis */
    private const CLEAR_WIN_MARGIN = 0.25;

    private const MAX_CANDIDATES = 5;

    private const IGNORED_WORDS = ['pt', 'cv', 'tbk', 'persero', 'ud', 'dan', 'and', 'the'];

    /** Nama bulan (Indonesia & Inggris, lengkap & singkatan) untuk membandingkan periode program */
    private const MONTHS = [
        'jan' => 1, 'januari' => 1, 'january' => 1,
        'feb' => 2, 'februari' => 2, 'february' => 2,
        'mar' => 3, 'maret' => 3, 'march' => 3,
        'apr' => 4, 'april' => 4,
        'mei' => 5, 'may' => 5,
        'jun' => 6, 'juni' => 6, 'june' => 6,
        'jul' => 7, 'juli' => 7, 'july' => 7,
        'agu' => 8, 'agt' => 8, 'agustus' => 8, 'aug' => 8, 'august' => 8,
        'sep' => 9, 'sept' => 9, 'september' => 9,
        'okt' => 10, 'oct' => 10, 'oktober' => 10, 'october' => 10,
        'nov' => 11, 'nop' => 11, 'november' => 11, 'nopember' => 11,
        'des' => 12, 'dec' => 12, 'desember' => 12, 'december' => 12,
    ];

    /**
     * @param  array  $extracted  hasil DocumentAiAnalysisService::analyzeFile
     * @param  string  $type  self::INVOICE atau self::FAKTUR
     * @return array{program: ?Program, reason: ?string, message: ?string, candidates: array<int, array>}
     */
    public static function match(array $extracted, string $type): array
    {
        $storedType = $type === self::FAKTUR ? 'faktur' : 'invoice';

        $scored = Program::query()
            ->withExists(['documents as has_same_type' => fn ($query) => $query->where('type', $storedType)])
            ->get()
            ->map(fn (Program $program) => self::score($program, $extracted, $type))
            ->sortByDesc('score')
            ->values();

        // Sudah terurut skor; kunci nomor (2) lebih kuat daripada kunci nilai DPP (1.5)
        $keyed = $scored->filter(fn ($row) => $row['key_reason'] !== null)->values();
        $locked = $keyed->filter(fn ($row) => $row['problem'] === null)->values();

        if ($locked->count() === 1 || ($locked->count() > 1 && $locked[0]['score'] - $locked[1]['score'] >= self::CLEAR_WIN_MARGIN)) {
            return [
                'program' => $locked[0]['program'],
                'reason' => $locked[0]['key_reason'],
                'message' => null,
                'candidates' => [],
            ];
        }

        if ($locked->count() > 1) {
            $reasons = $locked->pluck('key_reason')->unique()->implode(', ');

            return self::review($locked, "Ada {$locked->count()} program yang cocok ({$reasons}). Pilih yang benar.");
        }

        if ($keyed->isNotEmpty()) {
            // Nomornya ada di arsip, tapi badan usaha / periodenya tidak cocok: jangan dilampirkan otomatis
            return self::review($keyed, "{$keyed[0]['key_reason']} ditemukan, tapi {$keyed[0]['problem']}. Pilih programnya.");
        }

        $similar = $scored->filter(fn ($row) => $row['score'] >= 0.2)->values();

        return self::review($similar, self::notFoundMessage($extracted, $type));
    }

    private static function notFoundMessage(array $extracted, string $type): string
    {
        $sj = $extracted['sj_number'] ?? null;

        if ($type === self::FAKTUR) {
            return self::code($sj) !== ''
                ? "No. SJ {$sj} (Referensi) tidak ditemukan di arsip. Pilih programnya."
                : 'Referensi (No. SJ) di faktur tidak terbaca, dan No. Faktur/DPP-nya tidak ditemukan di arsip. Pilih programnya.';
        }

        return self::code($sj) !== '' || self::code($extracted['invoice_no'] ?? null) !== ''
            ? 'No. SJ/Invoice di dokumen tidak ditemukan di arsip. Pilih programnya.'
            : 'No. Surat Jalan tidak terbaca. Pilih programnya.';
    }

    private static function review(Collection $rows, string $message): array
    {
        return [
            'program' => null,
            'reason' => null,
            'message' => $message,
            'candidates' => $rows->take(self::MAX_CANDIDATES)->map(fn ($row) => [
                'id' => (string) $row['program']->id,
                'title' => $row['program']->title,
                'company_name' => $row['program']->company_name,
                'po_sj_number' => $row['program']->po_sj_number,
                'category' => $row['program']->category,
                'supplier' => $row['program']->supplier,
            ])->values()->all(),
        ];
    }

    private static function score(Program $program, array $extracted, string $type): array
    {
        $party = self::partySimilarity($extracted, $program);
        $title = self::similarity($extracted['program_name'] ?? null, $program->title);
        $category = self::similarity($extracted['description'] ?? null, $program->category);
        [$keyReason, $keyWeight] = self::keyReason($program, $extracted, $type, max($party, $title));

        $score = $keyWeight
            + 0.4 * $party
            + 0.3 * $category
            + 0.3 * $title
            // Program yang belum punya dokumen jenis ini lebih mungkin menjadi tujuannya
            + ($program->has_same_type ? 0 : 0.1);

        return [
            'program' => $program,
            'key_reason' => $keyReason,
            'problem' => $keyReason === null ? null : self::lockProblem($program, $extracted),
            'score' => $score,
        ];
    }

    /**
     * @return array{0: ?string, 1: float} alasan kecocokan dan bobotnya (nomor 2, nilai DPP 1.5, tidak ada 0)
     */
    private static function keyReason(Program $program, array $extracted, string $type, float $nameSimilarity): array
    {
        $sj = self::code($extracted['sj_number'] ?? null);
        $sjFound = strlen($sj) >= self::MIN_KEY_LENGTH;
        if ($sjFound && str_contains(self::code($program->po_sj_number), $sj)) {
            return ['No. SJ ' . $extracted['sj_number'], 2.0];
        }

        if ($type === self::FAKTUR) {
            $faktur = self::digits($extracted['faktur_number'] ?? null);
            if (strlen($faktur) >= self::MIN_FAKTUR_DIGITS && self::digits($program->faktur_number) === $faktur) {
                return ['No. Faktur ' . $extracted['faktur_number'], 2.0];
            }

            // Hanya bila Referensi (No. SJ) tidak terbaca: DPP sama (dan PPN bila terbaca), untuk pihak/program yang memang mirip
            $sameDpp = self::sameAmount($extracted['dpp_amount'] ?? null, $program->dpp_amount);
            $samePpn = empty($extracted['ppn_amount']) || self::sameAmount($extracted['ppn_amount'], $program->ppn_amount);
            if (!$sjFound && $sameDpp && $samePpn && $nameSimilarity >= self::MIN_NAME_SIMILARITY_FOR_AMOUNT) {
                return ['DPP Rp ' . number_format((float) $extracted['dpp_amount'], 0, ',', '.'), 1.5];
            }

            return [null, 0.0];
        }

        $invoiceNo = self::code($extracted['invoice_no'] ?? null);
        if (strlen($invoiceNo) >= self::MIN_KEY_LENGTH && self::code($program->invoice_no) === $invoiceNo) {
            return ['No. Invoice ' . $extracted['invoice_no'], 2.0];
        }

        return [null, 0.0];
    }

    /**
     * Alasan dokumen tidak boleh dilampirkan otomatis walau nomornya cocok, atau null bila aman
     */
    private static function lockProblem(Program $program, array $extracted): ?string
    {
        $entity = self::entitySimilarity($extracted, $program);
        if ($entity === null) {
            return 'penjual/pembelinya tidak bisa dipastikan';
        }
        if ($entity < self::MIN_PARTY_SIMILARITY) {
            return 'badan usahanya berbeda';
        }
        if (self::periodConflict($extracted['program_name'] ?? null, $program->title)) {
            return 'bulan/tahun programnya berbeda';
        }

        return null;
    }

    /**
     * Kemiripan pihak dokumen (pembeli & penjual) dengan company & supplier program, rata-rata untuk skor.
     * Urutannya tidak dipastikan: di sebagian data, company berisi penjual dan supplier berisi pembeli.
     */
    private static function partySimilarity(array $extracted, Program $program): float
    {
        $buyer = $extracted['bill_to'] ?? null;
        $seller = $extracted['supplier_name'] ?? null;

        return max(
            (self::similarity($buyer, $program->company_name) + self::similarity($seller, $program->supplier)) / 2,
            (self::similarity($buyer, $program->supplier) + self::similarity($seller, $program->company_name)) / 2
        );
    }

    /**
     * Badan usaha untuk kunci: penjual DAN pembeli harus cocok, jadi diambil yang terendah dari keduanya
     * (dengan susunan company/supplier terbaik). Null bila ada nama yang kosong sehingga tidak bisa dipastikan.
     */
    private static function entitySimilarity(array $extracted, Program $program): ?float
    {
        $buyer = $extracted['bill_to'] ?? null;
        $seller = $extracted['supplier_name'] ?? null;
        foreach ([$buyer, $seller, $program->company_name, $program->supplier] as $name) {
            if (!self::words($name)) {
                return null;
            }
        }

        return max(
            min(self::similarity($buyer, $program->company_name), self::similarity($seller, $program->supplier)),
            min(self::similarity($buyer, $program->supplier), self::similarity($seller, $program->company_name))
        );
    }

    /** Bulan atau tahun sama-sama tertulis di deskripsi dokumen dan nama program, tapi berbeda (mis. SEPTEMBER vs AUGUST 2026) */
    private static function periodConflict(?string $documentText, ?string $programTitle): bool
    {
        [$documentMonths, $documentYears] = self::period($documentText);
        [$programMonths, $programYears] = self::period($programTitle);

        return ($documentMonths && $programMonths && !array_intersect($documentMonths, $programMonths))
            || ($documentYears && $programYears && !array_intersect($documentYears, $programYears));
    }

    /** @return array{0: int[], 1: string[]} bulan (1-12) dan tahun (20xx) yang tertulis di teks */
    private static function period(?string $text): array
    {
        $months = [];
        $years = [];
        foreach (self::words($text) as $word) {
            if (isset(self::MONTHS[$word])) {
                $months[] = self::MONTHS[$word];
            } elseif (preg_match('/^20\d{2}$/', $word)) {
                $years[] = $word;
            }
        }

        return [array_values(array_unique($months)), $years];
    }

    private static function sameAmount($a, $b): bool
    {
        return is_numeric($a) && (float) $a > 0 && is_numeric($b) && abs((float) $a - (float) $b) <= self::AMOUNT_TOLERANCE;
    }

    /** Huruf & angka saja, huruf besar: "No. 2609-000804" → "2609000804" */
    private static function code(?string $value): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
    }

    private static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }

    /** Kemiripan kata (koefisien Dice), 0 sampai 1 */
    private static function similarity(?string $a, ?string $b): float
    {
        $wordsA = self::words($a);
        $wordsB = self::words($b);
        if (!$wordsA || !$wordsB) {
            return 0.0;
        }

        return 2 * count(array_intersect($wordsA, $wordsB)) / (count($wordsA) + count($wordsB));
    }

    private static function words(?string $text): array
    {
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii((string) $text)));

        return array_values(array_unique(array_filter(
            explode(' ', $normalized),
            fn ($word) => $word !== '' && !in_array($word, self::IGNORED_WORDS, true)
        )));
    }
}
