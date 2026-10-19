<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentUploadLog;
use Illuminate\Http\Request;

/**
 * Riwayat dokumen yang masuk ke arsip (menu Riwayat)
 */
class DocumentUploadLogController extends Controller
{
    private const STATUSES = ['matched', 'duplicate', 'review', 'failed', 'skipped'];

    private const SOURCES = [DocumentUploadLog::SOURCE_AUTO, DocumentUploadLog::SOURCE_MANUAL, DocumentUploadLog::SOURCE_WHMS];

    /**
     * GET /api/document-logs?status=&source=&q=&page=
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:' . implode(',', self::STATUSES),
            'source' => 'nullable|in:' . implode(',', self::SOURCES),
            'q' => 'nullable|string|max:100',
        ]);

        $search = trim((string) ($validated['q'] ?? ''));

        $logs = DocumentUploadLog::query()
            ->with('program:id,title,company_name')
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['source'] ?? null, fn ($query, $source) => $query->where('source', $source))
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $like = "%{$search}%";
                $inner->where('file_name', 'like', $like)
                    ->orWhere('number', 'like', $like)
                    ->orWhere('uploaded_by', 'like', $like)
                    ->orWhereHas('program', fn ($program) => $program->where('title', 'like', $like));
            }))
            ->latest('id')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'logs' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * POST /api/document-logs/{id}/skip: berkas "perlu dipilih" yang dilewati pengguna (tidak disimpan)
     */
    public function skip($id)
    {
        $log = DocumentUploadLog::findOrFail($id);
        if ($log->status === 'review') {
            $log->update(['status' => 'skipped', 'message' => 'Dilewati, tidak disimpan.']);
        }

        return response()->json(['success' => true, 'log' => $log]);
    }
}
