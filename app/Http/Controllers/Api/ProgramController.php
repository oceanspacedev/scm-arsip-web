<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramDocument;
use App\Models\RawImport;
use App\Services\SeaweedStorageService;
use App\Services\DocumentAiAnalysisService;
use App\Services\ProgramDocumentStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ProgramController extends Controller
{
    /**
     * Nomor yang diisi AI per jenis dokumen: [kolom program (= kunci hasil AI), label].
     * Hanya nomor ini yang diubah; DPP, PPN, total, tanggal, dan data lain tidak disentuh.
     */
    private const AI_NUMBER_FIELDS = [
        'invoice' => ['invoice_no', 'No. Invoice'],
        'faktur' => ['faktur_number', 'No. Faktur'],
        'faktur_pajak' => ['faktur_number', 'No. Faktur'],
    ];

    /**
     * Get all programs with summary metrics
     */
    public function index()
    {
        $programs = Program::with('documents')->orderBy('due_date', 'desc')->get();

        $totalDpp = $programs->sum('dpp_amount');
        $totalPpn = $programs->sum('ppn_amount');
        $totalValue = $programs->sum('total_amount');
        $totalCount = $programs->count();
        $completeCount = $programs->where('status', 'Lengkap')->count();
        $attentionCount = $programs->where('status', 'Perlu Tindakan')->count();

        return response()->json([
            'success' => true,
            'programs' => $programs,
            'metrics' => [
                'total_dpp' => $totalDpp,
                'total_ppn' => $totalPpn,
                'total_value' => $totalValue,
                'total_count' => $totalCount,
                'complete_count' => $completeCount,
                'attention_count' => $attentionCount,
                'completeness_percentage' => $totalCount > 0 ? round(($completeCount / $totalCount) * 100) : 0
            ]
        ]);
    }

    /**
     * Get single program
     */
    public function show($id)
    {
        $program = Program::with('documents')->findOrFail($id);

        return response()->json([
            'success' => true,
            'program' => $program
        ]);
    }

    /**
     * Determine if a category or program name belongs to Non-PPN (PPN 0%)
     * Price Protection, Bonus, Rebate -> PPN 0
     * Marketing Service Fee, Branding, Purchase Order, and others -> PPN Ke isi (11%)
     */
    public static function isNonPpnCategory(?string $category, ?string $title = ''): bool
    {
        $cat = strtolower(trim((string)$category));
        $masterFile = storage_path('app/master_data.json');
        if (file_exists($masterFile)) {
            $data = json_decode(@file_get_contents($masterFile), true);
            if (!empty($data['ppn_rules']) && is_array($data['ppn_rules'])) {
                $rules = array_map(fn($r) => strtolower(trim($r)), $data['ppn_rules']);
                if ($cat !== '' && in_array($cat, $rules, true)) {
                    return true;
                }
                if ($cat !== '') {
                    return false;
                }
            }
        }

        $t = strtolower(trim((string)$title));
        $combined = $cat . ' ' . $t;

        if (
            str_contains($combined, 'price protection') ||
            str_contains($combined, 'price_protection') ||
            str_contains($combined, 'priceprotect') ||
            str_contains($combined, 'bonus') ||
            str_contains($combined, 'rebate')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Safely parse various date/month formats (including Indonesian month strings like 'Maret 2026')
     */
    private function parseSafeDate($val): string
    {
        if (empty($val)) {
            return Carbon::now()->toDateString();
        }

        if ($val instanceof \DateTimeInterface) {
            return Carbon::instance($val)->toDateString();
        }

        $str = trim((string) $val);

        // 1. If already standard YYYY-MM-DD or YYYY/MM/DD
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $str, $matches)) {
            $y = $matches[1];
            $m = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $d = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            return "{$y}-{$m}-{$d}";
        }

        // 2. If DD-MM-YYYY or DD/MM/YYYY or DD.MM.YYYY (must check before generic Carbon::parse to prevent American m/d/Y month-day swap)
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $str, $matches)) {
            $d = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $m = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $y = $matches[3];
            return "{$y}-{$m}-{$d}";
        }

        // 2b. If DD-MM-YY or DD/MM/YY (2 digit year)
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2})$/', $str, $matches)) {
            $d = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $m = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $yy = (int) $matches[3];
            $y = $yy < 50 ? (2000 + $yy) : (1900 + $yy);
            return "{$y}-{$m}-{$d}";
        }

        // 3. Excel numeric serial date (e.g. 45367 or 37677)
        if (is_numeric($str) && (float)$str > 1000 && (float)$str < 100000) {
            try {
                $days = (int) $str;
                return Carbon::create(1899, 12, 30)->addDays($days)->toDateString();
            } catch (\Throwable $e) {}
        }

        // 4. Map Indonesian month names to English
        $indoMonths = [
            'januari' => 'January',
            'februari' => 'February',
            'maret' => 'March',
            'april' => 'April',
            'mei' => 'May',
            'juni' => 'June',
            'juli' => 'July',
            'agustus' => 'August',
            'september' => 'September',
            'oktober' => 'October',
            'november' => 'November',
            'desember' => 'December',
            'jan' => 'Jan',
            'feb' => 'Feb',
            'mar' => 'Mar',
            'apr' => 'Apr',
            'jun' => 'Jun',
            'jul' => 'Jul',
            'agu' => 'Aug',
            'agt' => 'Aug',
            'sep' => 'Sep',
            'okt' => 'Oct',
            'nov' => 'Nov',
            'des' => 'Dec',
        ];

        $normalized = strtolower($str);
        foreach ($indoMonths as $indo => $eng) {
            $normalized = preg_replace('/\b' . preg_quote($indo, '/') . '\b/i', $eng, $normalized);
        }

        // Try standard Carbon parsing (handles "March 2026", "2026-03-01", etc.)
        try {
            return Carbon::parse($normalized)->toDateString();
        } catch (\Throwable $e) {}

        // If it's MM/YYYY or MM-YYYY
        if (preg_match('/^(\d{1,2})[\/\-](\d{4})$/', $str, $matches)) {
            $m = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $y = $matches[2];
            return "{$y}-{$m}-01";
        }

        // If it's YYYY-MM or YYYY/MM
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})$/', $str, $matches)) {
            $m = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $y = $matches[1];
            return "{$y}-{$m}-01";
        }

        // If only month name was passed (e.g. "Maret" -> "March")
        try {
            return Carbon::parse("1 " . $normalized . " " . date('Y'))->toDateString();
        } catch (\Throwable $e) {}

        return Carbon::now()->toDateString();
    }

    /**
     * Create new program
     */
    public function store(Request $request)
    {
        $userRole = $request->header('X-User-Role') ?: $request->input('user_role');
        if ($userRole) {
            $isFinance = str_contains(strtolower($userRole), 'finance') || str_contains(strtolower($userRole), 'pajak');
            $isGudang = str_contains(strtolower($userRole), 'gudang');
            $isAdmin = str_contains(strtolower($userRole), 'admin');

            if (($isFinance || $isGudang) && !$isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role Anda tidak memiliki wewenang untuk menambahkan data program baru.'
                ], 403);
            }
        }

        $title = $request->input('title') ?: $request->input('program_name');
        $supplier = $request->input('supplier');

        if (!$title || !$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Nama program dan supplier wajib diisi.'
            ], 422);
        }

        $category = $request->input('category') ?: 'Logistik';
        $companyName = $request->input('company_name') ?: 'PT SCM Nusantara';
        $poSjNumber = $request->input('po_sj_number') ?: $request->input('no_po_sj');

        if ($duplicate = Program::findDuplicate($companyName, $poSjNumber, $category)) {
            return $this->duplicateResponse($duplicate);
        }

        $isNonPpn = self::isNonPpnCategory($category, $title);

        $dpp = (float) ($request->input('dpp_amount') ?? $request->input('dpp') ?? 0);
        if ($isNonPpn) {
            $ppn = 0.0;
        } elseif ($request->has('ppn_amount') && is_numeric($request->input('ppn_amount'))) {
            $ppn = (float) $request->input('ppn_amount');
        } elseif ($request->has('ppn') && is_numeric($request->input('ppn'))) {
            $ppn = (float) $request->input('ppn');
        } else {
            $ppn = round($dpp * 0.11);
        }

        $total = (float) ($request->input('total_amount') ?? $request->input('total_invoice') ?? ($dpp + $ppn));
        if ($isNonPpn && ($total == 0 || $total > $dpp)) {
            $total = $dpp;
        }

        try {
            $existingMax = Program::whereRaw('id REGEXP "^[0-9]+$"')
                ->selectRaw('MAX(CAST(id AS UNSIGNED)) as max_id')
                ->value('max_id');
        } catch (\Throwable $e) {
            $existingMax = null;
        }
        $nextId = (string) ($existingMax ? ((int) $existingMax + 1) : (Program::count() + 1));
        $id = (string) ($request->input('id') ?: $nextId);

        $dueDate = $this->parseSafeDate($request->input('due_date') ?: $request->input('program_date'));

        $program = Program::create([
            'id' => $id,
            'title' => $title,
            'supplier' => $supplier,
            'npwp' => $request->input('npwp') ?: '01.000.000.0-000.000',
            'category' => $category,
            'brand' => $request->input('brand') ?: 'SCM',
            'company_name' => $companyName,
            'kode_gudang' => $request->input('kode_gudang') ?: $request->input('warehouse_code'),
            'po_sj_number' => $poSjNumber,
            'invoice_no' => $request->input('invoice_no') ?: $request->input('invoice_number') ?: ('INV/' . date('Y') . '/SCM/' . rand(1000, 9999)),
            'dpp_amount' => $dpp,
            'ppn_amount' => $ppn,
            'total_amount' => $total,
            'payment_status' => $request->input('payment_status') ?: 'WAITING PAYMENT',
            'pph_type' => $request->input('pph_type') ?: 'NON_PPH',
            'pph_amount' => (float) ($request->input('pph_amount') ?? $request->input('pph') ?? 0),
            'faktur_number' => $request->input('faktur_number') ?: $request->input('tax_invoice_number'),
            'faktur_date' => ($request->input('faktur_date') ?: $request->input('tax_invoice_date')) ? $this->parseSafeDate($request->input('faktur_date') ?: $request->input('tax_invoice_date')) : null,
            'tax_notes' => $request->input('tax_notes'),
            'is_verified' => (bool) $request->input('is_verified', false),
            'due_date' => $dueDate,
            'status' => $request->input('status') ?: 'Perlu Tindakan'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Program berhasil ditambahkan.',
            'program' => $program->load('documents')
        ], 201);
    }

    /**
     * Update program
     */
    public function update(Request $request, $id)
    {
        $userRole = $request->header('X-User-Role') ?: $request->input('user_role');

        $program = Program::findOrFail($id);

        $isFinance = $userRole && (str_contains(strtolower($userRole), 'finance') || str_contains(strtolower($userRole), 'pajak'));
        $isScm = $userRole && str_contains(strtolower($userRole), 'scm');
        $isGudang = $userRole && str_contains(strtolower($userRole), 'gudang');
        $isAdmin = !$userRole || str_contains(strtolower($userRole), 'admin');

        $title = $request->input('title') ?: $request->input('program_name');
        $invoiceNo = $request->input('invoice_no') ?: $request->input('invoice_number');
        $dueDate = $request->input('due_date') ?: $request->input('program_date');

        $data = [];

        // Purchase & Vendor fields (Admin SCM, Staff SCM, Staff Gudang)
        if ($isAdmin || $isScm || $isGudang) {
            if ($request->exists('title') || $request->exists('program_name')) {
                $t = $request->input('title') ?? $request->input('program_name');
                if (!empty($t)) $data['title'] = $t;
            }
            if ($request->exists('supplier')) $data['supplier'] = $request->input('supplier') ?: '';
            if ($request->exists('npwp')) $data['npwp'] = $request->input('npwp') ?: '01.000.000.0-000.000';
            if ($request->exists('category') && $request->input('category')) $data['category'] = $request->input('category');
            if ($request->exists('brand')) $data['brand'] = $request->input('brand');
            if ($request->exists('company_name')) $data['company_name'] = $request->input('company_name');
            if ($request->exists('kode_gudang') || $request->exists('warehouse_code')) {
                $data['kode_gudang'] = $request->input('kode_gudang') ?? $request->input('warehouse_code');
            }
            if ($request->exists('po_sj_number') || $request->exists('no_po_sj')) {
                $data['po_sj_number'] = $request->input('po_sj_number') ?? $request->input('no_po_sj');
            }
        }

        // Financial & Tax fields (Admin SCM, Staff SCM, & Finance)
        if ($isAdmin || $isScm || $isFinance) {
            if ($request->exists('invoice_no') || $request->exists('invoice_number')) {
                $data['invoice_no'] = $request->input('invoice_no') ?? $request->input('invoice_number');
            }
            if ($request->exists('dpp_amount') || $request->exists('dpp')) {
                $data['dpp_amount'] = (float) ($request->input('dpp_amount') ?? $request->input('dpp') ?? 0);
            }
            if ($request->exists('ppn_amount') || $request->exists('ppn')) {
                $data['ppn_amount'] = (float) ($request->input('ppn_amount') ?? $request->input('ppn') ?? 0);
            }
            if ($request->exists('total_amount') || $request->exists('total_invoice')) {
                $data['total_amount'] = (float) ($request->input('total_amount') ?? $request->input('total_invoice') ?? 0);
            }
            if ($request->exists('payment_status')) {
                $data['payment_status'] = $request->input('payment_status') ?: 'WAITING PAYMENT';
            }
            if ($request->exists('pph_type')) {
                $data['pph_type'] = $request->input('pph_type') ?: 'NON_PPH';
            }
            if ($request->exists('pph_amount') || $request->exists('pph')) {
                $data['pph_amount'] = (float) ($request->input('pph_amount') ?? $request->input('pph') ?? 0);
            }
            if ($request->exists('faktur_number') || $request->exists('tax_invoice_number')) {
                $data['faktur_number'] = $request->input('faktur_number') ?? $request->input('tax_invoice_number');
            }
            if ($request->exists('faktur_date') || $request->exists('tax_invoice_date')) {
                $fDate = $request->input('faktur_date') ?? $request->input('tax_invoice_date');
                $data['faktur_date'] = $fDate ? $this->parseSafeDate($fDate) : null;
            }
            if ($request->exists('tax_notes')) {
                $data['tax_notes'] = $request->input('tax_notes');
            }
            if ($request->exists('is_verified')) {
                $data['is_verified'] = (bool) $request->input('is_verified');
            }
            if ($request->exists('due_date') || $request->exists('program_date')) {
                $dDate = $request->input('due_date') ?? $request->input('program_date');
                if (!empty($dDate)) $data['due_date'] = $this->parseSafeDate($dDate);
            }
            if ($request->exists('status') && $request->input('status')) {
                $data['status'] = $request->input('status');
            }
        }

        // Cek duplikasi hanya jika Company / No. PO / Category berubah, supaya arsip lama
        // yang kebetulan sudah dobel tetap bisa diedit kolom lainnya
        $next = $data + $program->only(['company_name', 'po_sj_number', 'category']);
        $keyChanged = Program::duplicateKey($next['company_name'], $next['po_sj_number'], $next['category'])
            !== Program::duplicateKey($program->company_name, $program->po_sj_number, $program->category);
        if ($keyChanged) {
            $duplicate = Program::findDuplicate($next['company_name'], $next['po_sj_number'], $next['category']);
            if ($duplicate) {
                return $this->duplicateResponse($duplicate);
            }
        }

        if (!empty($data)) {
            $program->update($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Program berhasil diperbarui.',
            'program' => $program->load('documents')
        ]);
    }

    /**
     * Respon penolakan karena kombinasi Company + No. PO + Category sudah terdaftar
     */
    private function duplicateResponse(Program $existing)
    {
        return response()->json([
            'success' => false,
            'message' => "Data sudah terdaftar: Company \"{$existing->company_name}\", No. PO \"{$existing->po_sj_number}\", "
                . "dan Category \"{$existing->category}\" sudah tersimpan pada program \"{$existing->title}\" (ID {$existing->id}).",
            'duplicate' => $existing->only(['id', 'title', 'company_name', 'po_sj_number', 'category']),
        ], 409);
    }

    /**
     * Delete program
     */
    public function destroy(Request $request, $id)
    {
        $userRole = $request->header('X-User-Role') ?: $request->input('user_role');
        if ($userRole && !str_contains(strtolower($userRole), 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Admin SCM yang memiliki wewenang untuk menghapus program/arsip.'
            ], 403);
        }

        $program = Program::findOrFail($id);
        $program->documents()->delete();
        $program->delete();

        return response()->json([
            'success' => true,
            'message' => 'Program berhasil dihapus.'
        ]);
    }

    /**
     * Upload / Add Document to Program
     */
    public function uploadDocument(Request $request, $id)
    {
        $docType = $request->input('document_type') ?: $request->input('type', 'faktur_pajak');
        $userRole = $request->header('X-User-Role') ?: $request->input('user_role');

        if ($userRole) {
            $isGudang = str_contains(strtolower($userRole), 'gudang');
            $isFinance = str_contains(strtolower($userRole), 'finance') || str_contains(strtolower($userRole), 'pajak');

            if ($isGudang && !in_array($docType, ['mou', 'memo', 'do'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role Staff Gudang hanya diizinkan mengunggah dokumen Memo / Surat Jalan (DO).'
                ], 403);
            }

            if ($isFinance && in_array($docType, ['mou', 'memo', 'do'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role Finance hanya diizinkan mengunggah dokumen Invoice dan Faktur Pajak.'
                ], 403);
            }
        }

        $program = Program::with('documents')->find($id);
        $backendType = $docType;
        if ($docType === 'faktur_pajak') $backendType = 'faktur';
        if ($docType === 'mou') $backendType = 'memo';

        $fileName = $request->input('file_name');
        $fileSize = $request->input('file_size');
        $filePath = null;
        $fileContent = null;
        $fileExtension = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $fileName ?: $file->getClientOriginalName();
            // Isi berkas disimpan untuk analisis AI, karena berkas di S3 tidak ada di disk lokal
            $fileContent = $file->getContent();
            $fileExtension = strtolower($file->getClientOriginalExtension() ?: 'pdf');

            $stored = ProgramDocumentStorage::store($file, (string) $id);
            $filePath = $stored['path'];
            $fileSize = $stored['size'];
        }

        $docId = 'doc-' . time() . '-' . rand(10, 99);
        $fileUrl = ProgramDocument::urlFor((string) $id, $docId, $filePath);

        $docData = [
            'id' => $docId,
            'document_type' => $docType,
            'type' => $backendType,
            'file_name' => $fileName ?: ($docType . '-' . $id . '.pdf'),
            'file_size' => $fileSize ?: '1.2 MB',
            'file_path' => $filePath,
            'file_url' => $fileUrl,
            'uploaded_by' => $request->input('uploaded_by', 'Staff'),
            'uploaded_at' => Carbon::now()->isoFormat('D MMM Y, HH.mm')
        ];

        $aiAnalysis = null;
        $aiDetectedInfo = [];

        if ($program) {
            ProgramDocument::create([
                'id' => $docId,
                'program_id' => $program->id,
                'type' => $backendType,
                'file_name' => $docData['file_name'],
                'file_size' => $docData['file_size'],
                'file_path' => $filePath,
                'uploaded_at' => Carbon::now()
            ]);

            // Check completeness across distinct document types
            $types = $program->documents()->pluck('type')->toArray();
            if (count(array_unique($types)) >= 3) {
                $program->update(['status' => 'Lengkap']);
            }

            // Synchronous AI analysis only if NOT async_ai requested (hanya nomor dokumen yang diisi)
            $isAsyncAi = $request->boolean('async_ai', false);
            $aiField = self::AI_NUMBER_FIELDS[$backendType] ?? null;
            if (!$isAsyncAi && $fileContent !== null && $aiField) {
                try {
                    [$column, $label] = $aiField;
                    $analysis = $this->analyzeDocumentContent($fileContent, $fileExtension, $backendType);
                    if ($analysis['success']) {
                        $aiAnalysis = $analysis;
                        if (!empty($analysis[$column])) {
                            $program->update([$column => $analysis[$column]]);
                            $aiDetectedInfo[] = "{$label}: {$analysis[$column]}";
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('AI Analysis exception on upload: ' . $e->getMessage());
                }
            }
        }

        $uploadMessage = 'Dokumen berhasil diunggah.';
        if (!empty($aiDetectedInfo)) {
            $uploadMessage .= ' AI mendeteksi & mengisi ' . implode(', ', $aiDetectedInfo) . '.';
        }

        return response()->json([
            'success' => true,
            'message' => $uploadMessage,
            'document' => $docData,
            'ai_analysis' => $aiAnalysis,
            'program' => $program ? $program->fresh()->load('documents') : null
        ]);
    }

    /**
     * Tampilkan / unduh berkas dokumen, dari S3 atau folder lokal
     */
    public function documentFile($id, $docId)
    {
        $document = ProgramDocument::where('program_id', $id)->where('id', $docId)->firstOrFail();
        $stored = ProgramDocumentStorage::read($document->file_path);
        abort_unless($stored, 404, 'Berkas dokumen tidak ditemukan.');

        $fileName = $document->file_name ?: basename((string) $document->file_path);
        $asciiName = str_replace(['/', '\\', '%'], '_', Str::ascii($fileName));

        return response($stored['content'], 200, [
            'Content-Type' => $stored['mime'],
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $fileName, $asciiName),
        ]);
    }

    /**
     * Analisis AI atas isi berkas lewat salinan sementara (berkas di S3 tidak ada di disk lokal)
     */
    private function analyzeDocumentContent(string $content, string $extension, string $type): array
    {
        $base = tempnam(sys_get_temp_dir(), 'doc_');
        $path = $base . '.' . $extension;
        file_put_contents($path, $content);

        try {
            return DocumentAiAnalysisService::analyzeFile($path, $type);
        } finally {
            @unlink($path);
            @unlink($base);
        }
    }

    /**
     * Baca nomor dokumen yang sudah diunggah dengan AI: No. Invoice dari invoice, No. Faktur dari faktur pajak.
     * Hanya nomor itu yang diisi; DPP, PPN, total, dan data lain tidak diubah.
     */
    public function analyzeDocumentAi(Request $request, $id, $docId)
    {
        $program = Program::findOrFail($id);
        $document = ProgramDocument::where('program_id', $id)->where('id', $docId)->firstOrFail();

        $aiField = self::AI_NUMBER_FIELDS[$document->type] ?? null;
        if (!$aiField) {
            return response()->json([
                'success' => false,
                'message' => 'AI hanya membaca nomor dari dokumen invoice dan faktur pajak.'
            ], 422);
        }
        [$column, $label] = $aiField;

        $stored = ProgramDocumentStorage::read($document->file_path);
        if (!$stored) {
            return response()->json([
                'success' => false,
                'message' => 'File fisik tidak ditemukan pada server.'
            ], 404);
        }

        $extension = strtolower(pathinfo((string) $document->file_path, PATHINFO_EXTENSION) ?: 'pdf');
        $analysis = $this->analyzeDocumentContent($stored['content'], $extension, $document->type);
        if (!$analysis['success']) {
            return response()->json([
                'success' => false,
                'message' => 'AI belum bisa membaca dokumen ini (' . rtrim($analysis['message'] ?? 'layanan AI error', '.') . "). Isi {$label} secara manual."
            ], 422);
        }

        if (empty($analysis[$column])) {
            return response()->json([
                'success' => false,
                'message' => "AI tidak menemukan {$label} di dokumen ini. Isi secara manual."
            ], 422);
        }

        $program->update([$column => $analysis[$column]]);

        return response()->json([
            'success' => true,
            'message' => "{$label} terisi otomatis: {$analysis[$column]}",
            'ai_analysis' => $analysis,
            'program' => $program->fresh()->load('documents')
        ]);
    }

    /**
     * Delete Document
     */
    public function deleteDocument(Request $request, $programId, $docId)
    {
        $decodedDocId = urldecode($docId);
        $cleanDocId = basename($decodedDocId);

        $matchingDocs = ProgramDocument::where(function ($q) use ($programId) {
                $q->where('program_id', $programId)
                  ->orWhereNull('program_id');
            })
            ->where(function ($q) use ($docId, $decodedDocId, $cleanDocId) {
                $q->where('id', $docId)
                  ->orWhere('id', $decodedDocId)
                  ->orWhere('file_name', $docId)
                  ->orWhere('file_name', $decodedDocId)
                  ->orWhere('file_name', $cleanDocId)
                  ->orWhere('file_name', 'like', '%' . $cleanDocId . '%')
                  ->orWhere('type', $docId)
                  ->orWhere('type', $docId === 'faktur_pajak' ? 'faktur' : ($docId === 'mou' ? 'memo' : $docId));
            })->get();

        if ($matchingDocs->isEmpty()) {
            $matchingDocs = ProgramDocument::where('id', $docId)
                ->orWhere('id', $decodedDocId)
                ->orWhere('file_name', $docId)
                ->orWhere('file_name', $decodedDocId)
                ->orWhere('file_name', $cleanDocId)
                ->orWhere('file_name', 'like', '%' . $cleanDocId . '%')
                ->get();
        }

        $docType = $matchingDocs->first() ? $matchingDocs->first()->type : $docId;

        $userRole = $request->header('X-User-Role') ?: $request->input('user_role');
        if ($userRole) {
            $isGudang = str_contains(strtolower($userRole), 'gudang');
            $isFinance = str_contains(strtolower($userRole), 'finance') || str_contains(strtolower($userRole), 'pajak');
            $isAdmin = str_contains(strtolower($userRole), 'admin');

            if (!$isAdmin) {
                if ($isGudang && in_array($docType, ['invoice', 'faktur', 'faktur_pajak'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Role Gudang tidak diizinkan menghapus dokumen Invoice atau Faktur Pajak.'
                    ], 403);
                }

                if ($isFinance && in_array($docType, ['mou', 'memo', 'do'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Role Finance tidak diizinkan menghapus dokumen DO / Surat Jalan.'
                    ], 403);
                }
            }
        }

        foreach ($matchingDocs as $d) {
            ProgramDocumentStorage::delete($d->file_path);
            $d->delete();
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('incoming_email_documents')) {
            try {
                \Illuminate\Support\Facades\DB::table('incoming_email_documents')
                    ->where(function ($q) use ($programId) {
                        $q->where('program_id', $programId)
                          ->orWhereNull('program_id');
                    })
                    ->where(function ($q) use ($docId, $decodedDocId, $cleanDocId) {
                        $q->where('id', $docId)
                          ->orWhere('id', $decodedDocId)
                          ->orWhere('file_name', $docId)
                          ->orWhere('file_name', $decodedDocId)
                          ->orWhere('file_name', $cleanDocId)
                          ->orWhere('file_name', 'like', '%' . $cleanDocId . '%');
                    })
                    ->delete();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $program = Program::find($programId);
        if ($program) {
            $updates = [];

            // Bersihkan nomor invoice HANYA jika tidak ada lagi dokumen invoice yang tersisa
            if ($docType === 'invoice') {
                $hasOtherInvoice = $program->documents()->where('type', 'invoice')->exists();
                if (!$hasOtherInvoice) {
                    $updates['invoice_no'] = null;
                }
            }
            // Bersihkan nomor & tanggal faktur pajak HANYA jika tidak ada lagi dokumen FP yang tersisa
            if ($docType === 'faktur' || $docType === 'faktur_pajak') {
                $hasOtherFaktur = $program->documents()->whereIn('type', ['faktur', 'faktur_pajak'])->exists();
                if (!$hasOtherFaktur) {
                    $updates['faktur_number'] = null;
                    $updates['faktur_date'] = null;
                }
            }

            $types = $program->documents()->pluck('type')->toArray();
            if (count(array_unique($types)) < 3) {
                $updates['status'] = 'Perlu Tindakan';
            }

            if (!empty($updates)) {
                $program->update($updates);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil dihapus.',
            'program' => $program ? $program->fresh()->load('documents') : null
        ]);
    }

    /**
     * Import multiple programs (Excel/JSON) and persist raw file to SeaweedFS / S3 storage
     */
    public function import(Request $request)
    {
        $userRole = $request->header('X-User-Role') ?: $request->input('user_role');
        if ($userRole) {
            $isFinance = str_contains(strtolower($userRole), 'finance') || str_contains(strtolower($userRole), 'pajak');
            $isGudang = str_contains(strtolower($userRole), 'gudang');
            $isAdmin = str_contains(strtolower($userRole), 'admin');

            if (($isFinance || $isGudang) && !$isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role Anda tidak memiliki wewenang untuk mengimpor data program.'
                ], 403);
            }
        }

        try {
            // 1. Parse program rows (can be JSON string or array)
            $programsInput = $request->input('programs', []);
            if (is_string($programsInput)) {
                $items = json_decode($programsInput, true) ?: [];
            } else {
                $items = is_array($programsInput) ? $programsInput : [];
            }

            // 2. Tolak baris yang kombinasi Company + No. PO + Category-nya sudah terdaftar,
            //    baris lain tetap diimpor. Jika semua baris duplikat, upload ditolak seluruhnya.
            $items = array_map(fn ($p) => $this->normalizeImportKeyFields($p), $items);
            [$items, $duplicates] = $this->rejectDuplicateRows($items);

            if (empty($items) && !empty($duplicates)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload ditolak: semua data (' . count($duplicates) . ') sudah terdaftar dengan kombinasi '
                        . 'Company, No. PO, dan Category yang sama.',
                    'imported_count' => 0,
                    'skipped_count' => count($duplicates),
                    'duplicates' => $duplicates
                ], 409);
            }

            // 3. Upload Raw File to SeaweedFS / S3 storage if file is present
            $rawImport = null;
            if ($request->hasFile('file')) {
                try {
                    $seaweed = new SeaweedStorageService();
                    $uploadedFile = $request->file('file');
                    $storageResult = $seaweed->uploadRawFile(
                        $uploadedFile,
                        $uploadedFile->getClientOriginalName(),
                        'mentahan_excel'
                    );

                    $rawImport = RawImport::create([
                        'file_name' => $storageResult['file_name'],
                        'file_key' => $storageResult['file_key'],
                        'file_size' => $storageResult['file_size'],
                        'file_url' => $storageResult['file_url'],
                        'storage_type' => $storageResult['storage'],
                        'imported_rows_count' => 0,
                        'uploaded_by' => $request->input('uploaded_by', 'Admin SCM'),
                        'notes' => $storageResult['warning'] ?? 'Tersimpan di storage SeaweedFS'
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Error uploading raw import file: ' . $e->getMessage());
                }
            }

            try {
                $existingMax = Program::whereRaw('id REGEXP "^[0-9]+$"')
                    ->selectRaw('MAX(CAST(id AS UNSIGNED)) as max_id')
                    ->value('max_id');
            } catch (\Throwable $e) {
                $existingMax = null;
            }
            $nextNumericId = $existingMax ? ((int) $existingMax) : Program::count();

            $imported = 0;
            foreach ($items as $p) {
                $nextNumericId++;
                $id = isset($p['id']) && !empty($p['id']) ? (string) $p['id'] : (string) $nextNumericId;

                $dpp = (float) ($p['dpp_amount'] ?? $p['dpp'] ?? 0);
                $title = $p['title'] ?? $p['program_name'] ?? 'Program Pengadaan SCM';
                $category = $p['category'];

                $isNonPpn = self::isNonPpnCategory($category, $title);
                if ($isNonPpn) {
                    $ppn = 0.0;
                } elseif (isset($p['ppn_amount']) && is_numeric($p['ppn_amount'])) {
                    $ppn = (float) $p['ppn_amount'];
                } elseif (isset($p['ppn']) && is_numeric($p['ppn'])) {
                    $ppn = (float) $p['ppn'];
                } else {
                    $ppn = round($dpp * 0.11);
                }

                $total = (float) ($p['total_amount'] ?? $p['total_invoice'] ?? ($dpp + $ppn));
                if ($isNonPpn && ($total == 0 || $total > $dpp)) {
                    $total = $dpp;
                }

                $dueDate = $this->parseSafeDate($p['due_date'] ?? $p['program_date'] ?? null);
                $companyName = $p['company_name'];
                $kodeGudang = $p['kode_gudang'] ?? $p['warehouse_code'] ?? null;
                $poSjNumber = $p['po_sj_number'];
                $fakturNumber = $p['faktur_number'] ?? $p['tax_invoice_number'] ?? null;
                $fakturDate = $p['faktur_date'] ?? $p['tax_invoice_date'] ?? null;

                $rawPayStatus = $p['payment_status'] ?? $p['status_payment'] ?? 'WAITING PAYMENT';
                $payStatus = 'WAITING PAYMENT';
                if ($rawPayStatus) {
                    $psLower = strtolower(trim((string)$rawPayStatus));
                    if (str_contains($psLower, 'cbd')) $payStatus = 'cbd';
                    elseif (str_contains($psLower, 'tempo')) $payStatus = 'tempo';
                    elseif (str_contains($psLower, 'paid') || str_contains($psLower, 'lunas')) $payStatus = 'PAID';
                    else $payStatus = 'WAITING PAYMENT';
                }

                $program = Program::updateOrCreate(
                    ['id' => $id],
                    [
                        'title' => $p['title'] ?? $p['program_name'] ?? 'Program Pengadaan SCM',
                        'supplier' => $p['supplier'] ?? 'PT Rekanan Vendor',
                        'npwp' => $p['npwp'] ?? '01.000.000.0-000.000',
                        'category' => $category,
                        'brand' => !empty($p['brand']) ? trim($p['brand']) : 'SCM',
                        'company_name' => $companyName,
                        'kode_gudang' => $kodeGudang,
                        'po_sj_number' => $poSjNumber,
                        'invoice_no' => $p['invoice_no'] ?? $p['invoice_number'] ?? ('INV/' . date('Y') . '/SCM/' . rand(1000, 9999)),
                        'dpp_amount' => $dpp,
                        'ppn_amount' => $ppn,
                        'total_amount' => $total,
                        'payment_status' => $payStatus,
                        'pph_type' => $p['pph_type'] ?? 'NON_PPH',
                        'pph_amount' => (float) ($p['pph_amount'] ?? $p['pph'] ?? 0),
                        'faktur_number' => $fakturNumber,
                        'faktur_date' => !empty($fakturDate) ? $this->parseSafeDate($fakturDate) : null,
                        'tax_notes' => $p['tax_notes'] ?? null,
                        'is_verified' => (bool) ($p['is_verified'] ?? false),
                        'due_date' => $dueDate,
                        'status' => $p['status'] ?? 'Perlu Tindakan'
                    ]
                );

                // Import attached documents if present
                if (!empty($p['documents']) && is_array($p['documents'])) {
                    foreach ($p['documents'] as $d) {
                        $rawType = $d['document_type'] ?? $d['type'] ?? 'invoice';
                        $bType = $rawType === 'faktur_pajak' ? 'faktur' : ($rawType === 'mou' ? 'memo' : $rawType);

                        ProgramDocument::updateOrCreate(
                            ['id' => $d['id'] ?? ('doc-' . Str::random(8))],
                            [
                                'program_id' => $program->id,
                                'type' => $bType,
                                'file_name' => $d['file_name'] ?? ($rawType . '.pdf'),
                                'file_size' => $d['file_size'] ?? '1.2 MB',
                                'uploaded_at' => Carbon::now()
                            ]
                        );
                    }
                }
                $imported++;
            }

            if ($rawImport && $imported > 0) {
                $rawImport->update(['imported_rows_count' => $imported]);
            }

            $allPrograms = Program::with('documents')->orderBy('due_date', 'desc')->get();

            $storageMessage = $rawImport ? ' Berkas mentahan tersimpan di SeaweedFS SCM.' : '';
            $duplicateMessage = $duplicates
                ? ' ' . count($duplicates) . ' data ditolak karena kombinasi Company, No. PO, dan Category sudah terdaftar.'
                : '';

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimpor {$imported} data program ke database.{$storageMessage}{$duplicateMessage}",
                'imported_count' => $imported,
                'skipped_count' => count($duplicates),
                'duplicates' => $duplicates,
                'programs' => $allPrograms,
                'raw_import' => $rawImport
            ]);
        } catch (\Throwable $e) {
            \Log::error('Import fatal error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file import: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Seragamkan kolom kunci duplikasi baris import, supaya yang dicek sama dengan yang disimpan
     */
    private function normalizeImportKeyFields(array $p): array
    {
        $po = trim((string) ($p['po_sj_number'] ?? $p['no_po_sj'] ?? ''));

        return array_merge($p, [
            'company_name' => trim((string) ($p['company_name'] ?? $p['company'] ?? 'PT SCM Nusantara')),
            'po_sj_number' => $po === '' ? null : $po,
            'category' => trim((string) ($p['category'] ?? 'Logistik')),
        ]);
    }

    /**
     * Pisahkan baris import yang kombinasi Company + No. PO + Category-nya sudah terdaftar
     * atau muncul lebih dari sekali dalam file. Baris tanpa No. PO tidak dicek.
     *
     * @return array{0: array, 1: array} [baris yang diimpor, baris yang ditolak]
     */
    private function rejectDuplicateRows(array $items): array
    {
        $registered = Program::groupByDuplicateKey(array_column($items, 'po_sj_number'));
        $seenKeys = [];
        $accepted = [];
        $rejected = [];

        foreach (array_values($items) as $index => $p) {
            $key = Program::duplicateKey($p['company_name'], $p['po_sj_number'], $p['category']);
            if ($key === null) {
                $accepted[] = $p;
                continue;
            }

            // Baris ber-id yang memperbarui arsipnya sendiri tanpa mengubah kombinasi bukan duplikat
            $owners = collect($registered[$key] ?? []);
            $updatesItself = !empty($p['id']) && $owners->contains(fn (Program $owner) => (string) $owner->id === (string) $p['id']);
            $existing = $updatesItself ? null : $owners->first();

            if ($existing || isset($seenKeys[$key])) {
                $rejected[] = [
                    'row' => $index + 1, // nomor baris sesuai urutan data yang dikirim (kolom NO di preview)
                    'company_name' => $p['company_name'],
                    'po_sj_number' => $p['po_sj_number'],
                    'category' => $p['category'],
                    'reason' => $existing ? 'registered' : 'in_file',
                    'existing_id' => $existing?->id,
                ];
                continue;
            }

            $seenKeys[$key] = true;
            $accepted[] = $p;
        }

        return [$accepted, $rejected];
    }

    /**
     * Get list of raw imported Excel files
     */
    public function rawImports()
    {
        $imports = RawImport::orderBy('created_at', 'desc')->get();
        return response()->json([
            'success' => true,
            'raw_imports' => $imports
        ]);
    }

    /**
     * Delete a raw import log entry
     */
    public function deleteRawImport($id)
    {
        $item = RawImport::find($id);
        if ($item) {
            $item->delete();
        }
        return response()->json([
            'success' => true,
            'message' => 'Riwayat file mentahan telah dihapus.'
        ]);
    }

    /**
     * Download or stream raw import file
     */
    public function downloadRawImport($id)
    {
        $raw = RawImport::findOrFail($id);

        // 1. Try local backup file
        if ($raw->file_key) {
            $localPath = public_path('uploads/mentahan_excel/' . basename($raw->file_key));
            if (file_exists($localPath)) {
                return response()->download($localPath, $raw->file_name);
            }
        }

        // 2. Try fetching from SeaweedFS S3
        if ($raw->file_key) {
            $seaweed = new SeaweedStorageService();
            $obj = $seaweed->getObject($raw->file_key);
            if ($obj && !empty($obj['content'])) {
                return response($obj['content'])
                    ->header('Content-Type', $obj['mime'])
                    ->header('Content-Disposition', 'attachment; filename="' . $raw->file_name . '"');
            }
        }

        // 3. Redirect to file_url
        if ($raw->file_url) {
            return redirect($raw->file_url);
        }

        abort(404, 'Berkas mentahan tidak ditemukan.');
    }
}
