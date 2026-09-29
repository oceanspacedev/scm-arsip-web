<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentAiAnalysisService
{
    /**
     * Analyze uploaded document using OpenAI-compatible Gemini endpoint
     *
     * @param string $absoluteFilePath
     * @param string|null $documentType Hint: 'invoice', 'faktur_pajak', 'faktur', 'mou', etc.
     * @return array
     */
    public static function analyzeFile(string $absoluteFilePath, ?string $documentType = null): array
    {
        $baseUrl = rtrim(config('services.ai.base_url') ?: env('OPENAI_COMPATIBLE_BASE_URL', 'https://router.rizqis.com/v1'), '/');
        $apiKey = config('services.ai.api_key') ?: env('OPENAI_COMPATIBLE_API_KEY');
        $model = config('services.ai.model') ?: env('OPENAI_COMPATIBLE_MODEL', 'ag/gemini-3.7-flash-low');

        if (!$apiKey) {
            Log::warning('AI Analysis skipped: OPENAI_COMPATIBLE_API_KEY is not configured.');
            return [
                'success' => false,
                'message' => 'API key AI belum dikonfigurasi.'
            ];
        }

        if (!file_exists($absoluteFilePath)) {
            Log::error("AI Analysis file not found: {$absoluteFilePath}");
            return [
                'success' => false,
                'message' => 'File dokumen tidak ditemukan di server.'
            ];
        }

        $extension = strtolower(pathinfo($absoluteFilePath, PATHINFO_EXTENSION));
        $mimeMap = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp'
        ];
        $mimeType = $mimeMap[$extension] ?? mime_content_type($absoluteFilePath) ?? 'application/pdf';

        $fileData = @file_get_contents($absoluteFilePath);
        if (!$fileData) {
            return [
                'success' => false,
                'message' => 'Gagal membaca berkas file.'
            ];
        }

        $base64 = base64_encode($fileData);
        $dataUrl = "data:{$mimeType};base64,{$base64}";

        $systemPrompt = "Anda adalah asisten AI ahli ekstraksi dokumen keuangan, invoice (faktur tagihan), dan Faktur Pajak DJP Indonesia (e-Faktur). "
            . "Tugas Anda adalah membaca berkas dokumen yang diberikan dengan teliti dan mengekstrak nomor dokumen serta data terkait. "
            . "Wajib mengembalikan respon HANYA dalam format JSON valid tanpa tanda backtick markdown atau penjelasan lainnya.";

        $typeHint = $documentType ? "Petunjuk jenis dokumen yang diunggah pengguna: '{$documentType}'." : "";

        $userPrompt = "Analisis dokumen terlampir ({$extension}). {$typeHint}\n"
            . "Ekstrak informasi berikut jika terdapat di dalam dokumen:\n"
            . "1. 'invoice_no': Nomor Invoice / Faktur Penjualan / Nomor Tagihan (contoh: INV/2026/0101, INV-9812, dsb). Jika tidak ada kembalikan null.\n"
            . "2. 'faktur_number': Nomor Seri Faktur Pajak e-Faktur DJP 16 digit (contoh: 010.002-26.11029381 atau 0100022611029381). Jika tidak ada kembalikan null.\n"
            . "3. 'faktur_date': Tanggal faktur pajak atau invoice dalam format 'YYYY-MM-DD'. Jika tidak ada kembalikan null.\n"
            . "4. 'dpp_amount': Nilai Dasar Pengenaan Pajak (DPP) sebagai angka murni (float/integer) tanpa simbol titik/koma ribuan. Jika tidak ada kembalikan null.\n"
            . "5. 'ppn_amount': Nilai PPN sebagai angka murni tanpa simbol titik/koma ribuan. Jika tidak ada kembalikan null.\n"
            . "6. 'total_amount': Nilai Total Tagihan / Total Invoice sebagai angka murni tanpa simbol titik/koma ribuan. Jika tidak ada kembalikan null.\n"
            . "7. 'payment_status': Status pembayaran jika tertera di dokumen. Pilihan: 'cbd' (Cash Before Delivery), 'tempo', 'WAITING PAYMENT', atau 'PAID' (Lunas). Jika tidak tertera, kembalikan null.\n\n"
            . "Format output yang DIHARUSKAN (hanya JSON murni):\n"
            . "{\n"
            . "  \"invoice_no\": \"...\",\n"
            . "  \"faktur_number\": \"...\",\n"
            . "  \"faktur_date\": \"YYYY-MM-DD\",\n"
            . "  \"dpp_amount\": 0,\n"
            . "  \"ppn_amount\": 0,\n"
            . "  \"total_amount\": 0,\n"
            . "  \"payment_status\": null\n"
            . "}";

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->timeout(45)
                ->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $userPrompt
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $dataUrl
                                ]
                            ]
                        ]
                    ]
                ],
                'stream' => false,
                'temperature' => 0.1
            ]);

            if (!$response->successful()) {
                Log::error('AI Analysis failed HTTP ' . $response->status() . ': ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'Layanan AI mengembalikan respon error: ' . $response->status()
                ];
            }

            $jsonRes = $response->json();
            $content = $jsonRes['choices'][0]['message']['content'] ?? '';

            // Clean markdown code blocks if the model wrapped output in ```json ... ```
            $cleanJson = trim($content);
            if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $cleanJson, $matches)) {
                $cleanJson = trim($matches[1]);
            }

            $parsed = json_decode($cleanJson, true);
            if (!is_array($parsed)) {
                Log::warning('AI Analysis output was not valid JSON: ' . $content);
                return [
                    'success' => false,
                    'raw_content' => $content,
                    'message' => 'Gagal memproses format output dari AI.'
                ];
            }

            // Normalize extracted fields
            $invoiceNo = !empty($parsed['invoice_no']) ? trim((string)$parsed['invoice_no']) : null;
            $fakturNumber = !empty($parsed['faktur_number']) ? trim((string)$parsed['faktur_number']) : null;
            $fakturDate = !empty($parsed['faktur_date']) ? trim((string)$parsed['faktur_date']) : null;
            $paymentStatus = !empty($parsed['payment_status']) ? trim((string)$parsed['payment_status']) : null;

            // Validate date format YYYY-MM-DD
            if ($fakturDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fakturDate)) {
                $fakturDate = null;
            }

            // Normalize payment status if detected
            if ($paymentStatus) {
                $psLower = strtolower($paymentStatus);
                if (str_contains($psLower, 'cbd')) {
                    $paymentStatus = 'cbd';
                } elseif (str_contains($psLower, 'tempo')) {
                    $paymentStatus = 'tempo';
                } elseif (str_contains($psLower, 'paid') || str_contains($psLower, 'lunas')) {
                    $paymentStatus = 'PAID';
                } elseif (str_contains($psLower, 'waiting') || str_contains($psLower, 'belum') || str_contains($psLower, 'pending')) {
                    $paymentStatus = 'WAITING PAYMENT';
                }
            }

            return [
                'success' => true,
                'invoice_no' => $invoiceNo,
                'faktur_number' => $fakturNumber,
                'faktur_date' => $fakturDate,
                'dpp_amount' => isset($parsed['dpp_amount']) && is_numeric($parsed['dpp_amount']) ? (float)$parsed['dpp_amount'] : null,
                'ppn_amount' => isset($parsed['ppn_amount']) && is_numeric($parsed['ppn_amount']) ? (float)$parsed['ppn_amount'] : null,
                'total_amount' => isset($parsed['total_amount']) && is_numeric($parsed['total_amount']) ? (float)$parsed['total_amount'] : null,
                'payment_status' => $paymentStatus,
                'raw' => $parsed
            ];
        } catch (\Throwable $e) {
            Log::error('Exception during AI Document Analysis: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menganalisis dokumen: ' . $e->getMessage()
            ];
        }
    }
}
