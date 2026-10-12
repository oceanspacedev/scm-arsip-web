<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Endpoint untuk WHMS: petugas gudang mengunggah Memo/DO di WHMS,
 * lalu WHMS meneruskannya ke program (PO/SJ) yang kode gudangnya sama.
 */
class WhmsIntegrationController extends Controller
{
    /**
     * Daftar program (PO/SJ) milik satu kode gudang, atau semua gudang jika kode_gudang tidak dikirim
     * (dipakai admin WHMS untuk upload banyak Memo/DO sekaligus ke berbagai gudang).
     */
    public function programs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_gudang' => ['nullable', 'string', 'max:50'],
        ]);

        $query = filled($validated['kode_gudang'] ?? null)
            ? $this->forWarehouse(Program::query(), $validated['kode_gudang'])
            : Program::query()->whereNotNull('kode_gudang')->where('kode_gudang', '!=', '');

        $programs = $query
            ->withExists(['documents as has_memo' => fn (Builder $query) => $query->where('type', 'memo')])
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get(['id', 'title', 'po_sj_number', 'company_name', 'supplier', 'kode_gudang', 'status', 'created_at'])
            ->sortBy('has_memo')
            ->values();

        return response()->json([
            'success' => true,
            'programs' => $programs,
        ]);
    }

    /**
     * Terima file Memo/DO dari WHMS untuk satu program.
     */
    public function storeDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'string'],
            'kode_gudang' => ['required', 'string', 'max:50'],
            'whms_document_id' => ['required', 'uuid'],
            'uploaded_by' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
        ]);

        $program = $this->forWarehouse(Program::query()->whereKey($validated['program_id']), $validated['kode_gudang'])->first();

        if (! $program) {
            return response()->json([
                'success' => false,
                'message' => 'Program/PO tidak ditemukan untuk kode gudang ini.',
            ], 404);
        }

        // WHMS bisa mengirim ulang dokumen yang sama (retry); jangan buat dokumen ganda
        $documentId = 'whms-'.$validated['whms_document_id'];
        $document = ProgramDocument::find($documentId);

        if (! $document) {
            $file = $request->file('file');
            // Ekstensi diambil dari isi file yang sudah divalidasi, bukan dari nama file kiriman
            $extension = $file->extension() === 'jpeg' ? 'jpg' : $file->extension();
            $storedName = 'doc_'.time().'_'.Str::random(8).'.'.$extension;

            Storage::disk('arsip_documents')->putFileAs('', $file, $storedName);

            $document = ProgramDocument::create([
                'id' => $documentId,
                'program_id' => $program->id,
                'type' => 'memo',
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $this->formatSize($file->getSize()),
                'file_path' => 'uploads/documents/'.$storedName,
                'uploaded_at' => Carbon::now(),
            ]);

            // Sama seperti upload manual: lengkap jika Invoice, Faktur, dan Memo/DO sudah ada
            if ($program->documents()->distinct()->count('type') >= 3) {
                $program->update(['status' => 'Lengkap']);
            }
        }

        return response()->json([
            'success' => true,
            'program_id' => $program->id,
            'document_id' => $document->id,
            'file_url' => $document->file_url,
        ], $document->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Cocokkan kode gudang tanpa membedakan huruf besar/kecil dan spasi di tepi.
     */
    private function forWarehouse(Builder $query, string $kodeGudang): Builder
    {
        return $query->whereRaw('LOWER(TRIM(kode_gudang)) = ?', [mb_strtolower(trim($kodeGudang))]);
    }

    private function formatSize(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1).' MB'
            : round($bytes / 1024, 1).' KB';
    }
}
