<?php

namespace App\Models;

use App\Services\ProgramDocumentStorage;
use Illuminate\Database\Eloquent\Model;

class ProgramDocument extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $appends = ['file_url'];

    protected $fillable = [
        'id',
        'program_id',
        'type',
        'file_name',
        'file_size',
        'file_path',
        'uploaded_at'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime'
    ];

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function getFileUrlAttribute(): ?string
    {
        return self::urlFor($this->program_id, $this->id, $this->file_path);
    }

    /**
     * URL untuk membuka berkas. Berkas di S3 dilayani lewat backend (bucket tidak perlu publik),
     * berkas lokal langsung dari folder public.
     */
    public static function urlFor(?string $programId, string $documentId, ?string $filePath): ?string
    {
        if (!$filePath) {
            return null;
        }

        if (ProgramDocumentStorage::isInS3($filePath)) {
            return url('/api/programs/' . rawurlencode((string) $programId) . '/documents/' . rawurlencode($documentId) . '/file');
        }

        return url($filePath);
    }
}
