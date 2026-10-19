<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris riwayat untuk tiap dokumen yang masuk: dari mana, ke program mana, dan hasilnya.
 */
class DocumentUploadLog extends Model
{
    public const SOURCE_AUTO = 'auto';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_WHMS = 'whms';

    protected $fillable = [
        'program_id',
        'document_id',
        'file_name',
        'document_type',
        'source',
        'status',
        'reason',
        'message',
        'number',
        'uploaded_by',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }
}
