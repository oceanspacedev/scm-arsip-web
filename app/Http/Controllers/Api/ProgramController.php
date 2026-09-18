<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramDocument;
use App\Models\RawImport;
use App\Services\SeaweedStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProgramController extends Controller
{
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

        // Excel numeric serial date (e.g. 45367)
        if (is_numeric($str) && (float)$str > 30000 && (float)$str < 60000) {
            try {
                $days = (int) $str;
                return Carbon::create(1899, 12, 30)->addDays($days)->toDateString();
            } catch (\Throwable $e) {}
        }

        // Map Indonesian month names to English
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

        // Try DD/MM/YYYY or DD-MM-YYYY
        try {
            return Carbon::createFromFormat('d/m/Y', $str)->toDateString();
        } catch (\Throwable $e) {}

        try {
            return Carbon::createFromFormat('d-m-Y', $str)->toDateString();
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
        $title = $request->input('title') ?: $request->input('program_name');
        $supplier = $request->input('supplier');

        if (!$title || !$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Nama program dan supplier wajib diisi.'
            ], 422);
        }

        $dpp = (float) ($request->input('dpp_amount') ?? $request->input('dpp') ?? 0);
        $ppn = (float) ($request->input('ppn_amount') ?? $request->input('ppn') ?? ($dpp * 0.11));
        $total = (float) ($request->input('total_amount') ?? $request->input('total_invoice') ?? ($dpp + $ppn));

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
            'category' => $request->input('category') ?: 'Logistik',
            'brand' => $request->input('brand') ?: 'SCM',
            'company_name' => $request->input('company_name') ?: 'PT SCM Nusantara',
            'po_sj_number' => $request->input('po_sj_number') ?: $request->input('no_po_sj'),
            'invoice_no' => $request->input('invoice_no') ?: $request->input('invoice_number') ?: ('INV/' . date('Y') . '/SCM/' . rand(1000, 9999)),
            'dpp_amount' => $dpp,
            'ppn_amount' => $ppn,
            'total_amount' => $total,
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
        if ($userRole && str_contains(strtolower($userRole), 'scm') && !str_contains(strtolower($userRole), 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Role SCM hanya memiliki akses melihat arsip (View-Only).'
            ], 403);
        }

        $program = Program::findOrFail($id);

        $isGudang = $userRole && str_contains(strtolower($userRole), 'gudang');
        $isFinance = $userRole && (str_contains(strtolower($userRole), 'finance') || str_contains(strtolower($userRole), 'pajak'));
        $isAdmin = !$userRole || str_contains(strtolower($userRole), 'admin');

        $title = $request->input('title') ?: $request->input('program_name');
        $invoiceNo = $request->input('invoice_no') ?: $request->input('invoice_number');
        $dueDate = $request->input('due_date') ?: $request->input('program_date');

        $data = [];

        // Purchase & Vendor fields (Wewenang Gudang & Admin SCM)
        if ($isAdmin || $isGudang) {
            if ($title) $data['title'] = $title;
            if ($request->has('supplier')) $data['supplier'] = $request->input('supplier');
            if ($request->has('npwp')) $data['npwp'] = $request->input('npwp');
            if ($request->has('category')) $data['category'] = $request->input('category');
            if ($request->has('brand')) $data['brand'] = $request->input('brand');
            if ($request->has('company_name')) $data['company_name'] = $request->input('company_name');
            if ($request->has('po_sj_number') || $request->has('no_po_sj')) {
                $data['po_sj_number'] = $request->input('po_sj_number') ?? $request->input('no_po_sj');
            }
        }

        // Financial & Tax fields (Wewenang Finance & Admin SCM)
        if ($isAdmin || $isFinance) {
            if ($invoiceNo !== null) $data['invoice_no'] = $invoiceNo;
            if ($request->has('dpp_amount') || $request->has('dpp')) {
                $data['dpp_amount'] = (float) ($request->input('dpp_amount') ?? $request->input('dpp'));
            }
            if ($request->has('ppn_amount') || $request->has('ppn')) {
                $data['ppn_amount'] = (float) ($request->input('ppn_amount') ?? $request->input('ppn'));
            }
            if ($request->has('total_amount') || $request->has('total_invoice')) {
                $data['total_amount'] = (float) ($request->input('total_amount') ?? $request->input('total_invoice'));
            }
            if ($request->has('pph_type')) {
                $data['pph_type'] = $request->input('pph_type') ?: 'NON_PPH';
            }
            if ($request->has('pph_amount') || $request->has('pph')) {
                $data['pph_amount'] = (float) ($request->input('pph_amount') ?? $request->input('pph'));
            }
            if ($request->has('faktur_number') || $request->has('tax_invoice_number')) {
                $data['faktur_number'] = $request->input('faktur_number') ?? $request->input('tax_invoice_number');
            }
            if ($request->has('faktur_date') || $request->has('tax_invoice_date')) {
                $fDate = $request->input('faktur_date') ?? $request->input('tax_invoice_date');
                $data['faktur_date'] = $fDate ? $this->parseSafeDate($fDate) : null;
            }
            if ($request->has('tax_notes')) {
                $data['tax_notes'] = $request->input('tax_notes');
            }
            if ($request->has('is_verified')) {
                $data['is_verified'] = (bool) $request->input('is_verified');
            }
            if ($dueDate) $data['due_date'] = $this->parseSafeDate($dueDate);
            if ($request->has('status')) $data['status'] = $request->input('status');
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
            $isScm = str_contains(strtolower($userRole), 'scm') && !str_contains(strtolower($userRole), 'admin');
            $isAdmin = str_contains(strtolower($userRole), 'admin');

            if ($isScm) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role SCM hanya memiliki akses melihat (View-Only).'
                ], 403);
            }

            if ($isGudang && !in_array($docType, ['mou', 'memo', 'do'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role Gudang hanya diizinkan mengunggah dokumen DO / Surat Jalan.'
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
        $fileUrl = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $fileName ?: $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension() ?: 'pdf';
            $safeName = 'doc_' . time() . '_' . Str::random(8) . '.' . $extension;

            $destination = public_path('uploads/documents');
            if (!file_exists($destination)) {
                mkdir($destination, 0777, true);
            }

            $file->move($destination, $safeName);
            $filePath = 'uploads/documents/' . $safeName;
            $fileUrl = url('uploads/documents/' . $safeName);

            $bytes = filesize($destination . '/' . $safeName);
            $fileSize = $bytes >= 1048576 
                ? round($bytes / 1048576, 1) . ' MB' 
                : round($bytes / 1024, 1) . ' KB';
        }

        $docId = 'doc-' . time() . '-' . rand(10, 99);

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
        }

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diunggah.',
            'document' => $docData,
            'program' => $program ? $program->fresh()->load('documents') : null
        ]);
    }

    /**
     * Delete Document
     */
    public function deleteDocument(Request $request, $programId, $docId)
    {
        $userRole = $request->header('X-User-Role') ?: $request->input('user_role');
        if ($userRole) {
            $isGudang = str_contains(strtolower($userRole), 'gudang');
            $isFinance = str_contains(strtolower($userRole), 'finance') || str_contains(strtolower($userRole), 'pajak');
            $isScm = str_contains(strtolower($userRole), 'scm') && !str_contains(strtolower($userRole), 'admin');

            if ($isScm) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role SCM tidak diizinkan menghapus berkas dokumen.'
                ], 403);
            }

            if ($isGudang && in_array($docId, ['invoice', 'faktur', 'faktur_pajak'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role Gudang tidak diizinkan menghapus dokumen Invoice atau Faktur Pajak.'
                ], 403);
            }

            if ($isFinance && in_array($docId, ['mou', 'memo', 'do'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role Finance tidak diizinkan menghapus dokumen DO / Surat Jalan.'
                ], 403);
            }
        }

        $doc = ProgramDocument::where('program_id', $programId)
            ->where(function ($q) use ($docId) {
                $q->where('id', $docId)
                  ->orWhere('type', $docId)
                  ->orWhere('type', $docId === 'faktur_pajak' ? 'faktur' : ($docId === 'mou' ? 'memo' : $docId));
            })->first();

        if ($doc) {
            if ($doc->file_path && file_exists(public_path($doc->file_path))) {
                @unlink(public_path($doc->file_path));
            }
            $doc->delete();
        }

        $program = Program::find($programId);
        if ($program) {
            $types = $program->documents()->pluck('type')->toArray();
            if (count(array_unique($types)) < 3) {
                $program->update(['status' => 'Perlu Tindakan']);
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
        try {
            // 1. Upload Raw File to SeaweedFS / S3 storage if file is present
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

            // 2. Parse program rows (can be JSON string or array)
            $programsInput = $request->input('programs', []);
            if (is_string($programsInput)) {
                $items = json_decode($programsInput, true) ?: [];
            } else {
                $items = is_array($programsInput) ? $programsInput : [];
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
                $ppn = (float) ($p['ppn_amount'] ?? $p['ppn'] ?? ($dpp * 0.11));
                $total = (float) ($p['total_amount'] ?? $p['total_invoice'] ?? ($dpp + $ppn));

                $dueDate = $this->parseSafeDate($p['due_date'] ?? $p['program_date'] ?? null);
                $companyName = $p['company_name'] ?? $p['company'] ?? 'PT SCM Nusantara';
                $poSjNumber = $p['po_sj_number'] ?? $p['no_po_sj'] ?? null;
                $fakturNumber = $p['faktur_number'] ?? $p['tax_invoice_number'] ?? null;
                $fakturDate = $p['faktur_date'] ?? $p['tax_invoice_date'] ?? null;

                $program = Program::updateOrCreate(
                    ['id' => $id],
                    [
                        'title' => $p['title'] ?? $p['program_name'] ?? 'Program Pengadaan SCM',
                        'supplier' => $p['supplier'] ?? 'PT Rekanan Vendor',
                        'npwp' => $p['npwp'] ?? '01.000.000.0-000.000',
                        'category' => $p['category'] ?? 'Logistik',
                        'company_name' => $companyName,
                        'po_sj_number' => $poSjNumber,
                        'invoice_no' => $p['invoice_no'] ?? $p['invoice_number'] ?? ('INV/' . date('Y') . '/SCM/' . rand(1000, 9999)),
                        'dpp_amount' => $dpp,
                        'ppn_amount' => $ppn,
                        'total_amount' => $total,
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

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimpor {$imported} data program ke database.{$storageMessage}",
                'imported_count' => $imported,
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
