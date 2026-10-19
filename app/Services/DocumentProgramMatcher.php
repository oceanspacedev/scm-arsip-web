<?php

namespace App\Services;

use App\Models\Program;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Mencari program arsip untuk invoice / faktur pajak hasil bacaan AI.
 *
 * Dokumen hanya dilampirkan otomatis bila ada kunci kuat yang menunjuk tepat satu program:
 * - invoice: No. Surat Jalan yang tercantum di No. PO/SJ program, atau No. Invoice yang sama
 * - faktur pajak: No. Faktur yang sama, atau DPP (dan PPN) yang sama dengan pihak/nama program yang mirip
 * Bila kunci menunjuk beberapa program, pihak (company & supplier), jenis tagihan, dan nama program
 * dipakai untuk memilih, dan hanya bila pemenangnya jelas. Selain itu dikembalikan kandidat untuk dipilih.
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

    /** Selisih skor minimum agar kandidat teratas boleh dipilih otomatis */
    private const CLEAR_WIN_MARGIN = 0.25;

    private const MAX_CANDIDATES = 5;

    private const IGNORED_WORDS = ['pt', 'cv', 'tbk', 'persero', 'ud', 'dan', 'and', 'the'];

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

        $keyed = $scored->filter(fn ($row) => $row['key_reason'] !== null)->values();
        // $keyed sudah terurut skor; kunci nomor (2) lebih kuat daripada kunci nilai DPP (1.5)

        if ($keyed->count() === 1 || ($keyed->count() > 1 && $keyed[0]['score'] - $keyed[1]['score'] >= self::CLEAR_WIN_MARGIN)) {
            return [
                'program' => $keyed[0]['program'],
                'reason' => $keyed[0]['key_reason'],
                'message' => null,
                'candidates' => [],
            ];
        }

        $keyLabel = $type === self::FAKTUR ? 'No. Faktur/DPP' : 'No. SJ/Invoice';
        if ($keyed->count() > 1) {
            return self::review($keyed, "{$keyLabel} cocok dengan {$keyed->count()} program. Pilih yang benar.");
        }

        $similar = $scored->filter(fn ($row) => $row['score'] >= 0.2)->values();

        return self::review($similar, $type === self::FAKTUR
            ? 'No. Faktur dan nilai DPP tidak ditemukan di arsip. Pilih programnya.'
            : (self::code($extracted['sj_number'] ?? null) !== '' || self::code($extracted['invoice_no'] ?? null) !== ''
                ? 'No. SJ/Invoice di dokumen tidak ditemukan di arsip. Pilih programnya.'
                : 'No. Surat Jalan tidak terbaca. Pilih programnya.'));
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

        return ['program' => $program, 'key_reason' => $keyReason, 'score' => $score];
    }

    /**
     * @return array{0: ?string, 1: float} alasan kecocokan dan bobotnya (nomor 2, nilai DPP 1.5, tidak ada 0)
     */
    private static function keyReason(Program $program, array $extracted, string $type, float $nameSimilarity): array
    {
        if ($type === self::FAKTUR) {
            $faktur = self::digits($extracted['faktur_number'] ?? null);
            if (strlen($faktur) >= self::MIN_FAKTUR_DIGITS && self::digits($program->faktur_number) === $faktur) {
                return ['No. Faktur ' . $extracted['faktur_number'], 2.0];
            }

            // DPP sama (dan PPN bila terbaca), tapi hanya untuk pihak/program yang memang mirip
            $sameDpp = self::sameAmount($extracted['dpp_amount'] ?? null, $program->dpp_amount);
            $samePpn = empty($extracted['ppn_amount']) || self::sameAmount($extracted['ppn_amount'], $program->ppn_amount);
            if ($sameDpp && $samePpn && $nameSimilarity >= self::MIN_NAME_SIMILARITY_FOR_AMOUNT) {
                return ['DPP Rp ' . number_format((float) $extracted['dpp_amount'], 0, ',', '.'), 1.5];
            }

            return [null, 0.0];
        }

        $sj = self::code($extracted['sj_number'] ?? null);
        if (strlen($sj) >= self::MIN_KEY_LENGTH && str_contains(self::code($program->po_sj_number), $sj)) {
            return ['No. SJ ' . $extracted['sj_number'], 2.0];
        }

        $invoiceNo = self::code($extracted['invoice_no'] ?? null);
        if (strlen($invoiceNo) >= self::MIN_KEY_LENGTH && self::code($program->invoice_no) === $invoiceNo) {
            return ['No. Invoice ' . $extracted['invoice_no'], 2.0];
        }

        return [null, 0.0];
    }

    /**
     * Kemiripan pihak dokumen (pembeli & penjual) dengan company & supplier program. Urutannya tidak
     * dipastikan: di sebagian data, company berisi penjual dan supplier berisi pembeli.
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
