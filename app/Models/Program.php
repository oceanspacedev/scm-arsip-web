<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'supplier',
        'npwp',
        'category',
        'brand',
        'company_name',
        'po_sj_number',
        'invoice_no',
        'dpp_amount',
        'ppn_amount',
        'total_amount',
        'pph_type',
        'pph_amount',
        'faktur_number',
        'faktur_date',
        'tax_notes',
        'is_verified',
        'due_date',
        'status'
    ];

    protected $casts = [
        'dpp_amount' => 'float',
        'ppn_amount' => 'float',
        'total_amount' => 'float',
        'pph_amount' => 'float',
        'faktur_date' => 'date',
        'is_verified' => 'boolean',
        'due_date' => 'date'
    ];

    public function documents()
    {
        return $this->hasMany(ProgramDocument::class, 'program_id');
    }
}
