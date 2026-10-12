<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'kode_gudang',
        'po_sj_number',
        'invoice_no',
        'dpp_amount',
        'ppn_amount',
        'total_amount',
        'payment_status',
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

    /**
     * Kunci duplikasi arsip: Company + No. PO + Category, tanpa membedakan huruf besar/kecil
     * dan spasi di tepi. Null jika No. PO kosong, karena data tanpa PO tidak bisa dicek duplikasinya.
     */
    public static function duplicateKey(?string $company, ?string $po, ?string $category): ?string
    {
        $po = self::normalizeKeyPart($po);
        if ($po === '') {
            return null;
        }

        return implode("\0", [self::normalizeKeyPart($company), $po, self::normalizeKeyPart($category)]);
    }

    /**
     * Arsip lain yang sudah tersimpan dengan kombinasi Company + No. PO + Category yang sama.
     */
    public static function findDuplicate(?string $company, ?string $po, ?string $category): ?self
    {
        $key = self::duplicateKey($company, $po, $category);

        return $key === null ? null : (self::groupByDuplicateKey([$po])[$key][0] ?? null);
    }

    /**
     * Arsip tersimpan yang No. PO-nya ada di daftar, dikelompokkan per duplicateKey().
     *
     * @param  array<int, string|null>  $poNumbers
     * @return array<string, array<int, self>>
     */
    public static function groupByDuplicateKey(array $poNumbers): array
    {
        $normalized = collect($poNumbers)
            ->map(fn ($po) => self::normalizeKeyPart($po))
            ->reject(fn ($po) => $po === '')
            ->unique()
            ->values();

        $groups = [];
        foreach ($normalized->chunk(500) as $chunk) {
            $matches = static::query()
                ->whereIn(DB::raw('LOWER(TRIM(po_sj_number))'), $chunk->values()->all())
                ->get();

            foreach ($matches as $program) {
                $groups[self::duplicateKey($program->company_name, $program->po_sj_number, $program->category)][] = $program;
            }
        }

        return $groups;
    }

    private static function normalizeKeyPart(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}
