<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class MasterDataController extends Controller
{
    protected string $dataFile;

    public function __construct()
    {
        $this->dataFile = storage_path('app/master_data.json');
    }

    public static function getDefaultMasterData(): array
    {
        return [
            'categories' => [
                'Marketing Service Fee',
                'Price Protection',
                'Bonus',
                'Branding',
                'Purchase Order',
                'Rebate',
                'Promosi',
                'Cashback',
                'Sewa Display',
                'Listing Fee',
                'Digital Promo',
                'Distribusi',
                'Insentif',
                'Bundling',
                'Sampling',
                'Loyalty',
                'Diskon',
                'Event',
                'Logistik',
                'Kemitraan',
                'Pengadaan',
                'Operasional',
            ],
            'brands' => [
                'SCM',
                'SCTV',
                'Indosiar',
                'Vidio',
                'Mentari TV',
                'Moji',
                'SinemArt',
                'Rans Entertainment',
            ],
            'companies' => [
                'PT SCM Nusantara',
                'PT Surya Citra Media Tbk',
                'PT Indonesia Entertainment Group',
                'PT Surya Citra Televisi',
                'PT Indosiar Visual Mandiri',
            ],
            'warehouses' => [
                ['code' => 'GDG-JKT-01', 'name' => 'Gudang Utama Jakarta', 'location' => 'Jakarta Barat'],
                ['code' => 'GDG-JKT-02', 'name' => 'Gudang Transit Ancol', 'location' => 'Jakarta Utara'],
                ['code' => 'GDG-BDG-01', 'name' => 'Gudang Regional Bandung', 'location' => 'Bandung'],
                ['code' => 'GDG-SBY-01', 'name' => 'Gudang Regional Surabaya', 'location' => 'Surabaya'],
                ['code' => 'GDG-SMG-01', 'name' => 'Gudang Semarang', 'location' => 'Semarang'],
            ],
            'payment_statuses' => [
                'Waiting Payment',
                'CBD (Cash Before Delivery)',
                'Tempo',
                'Paid (Lunas)',
            ],
            'ppn_rules' => [
                'Price Protection',
                'Bonus',
                'Rebate',
            ],
            'suppliers' => [
                ['name' => 'PT Unilever Indonesia Tbk', 'npwp' => '01.234.567.8-901.000', 'phone' => '021-52995299'],
                ['name' => 'PT Indofood CBP Sukses Makmur', 'npwp' => '01.345.678.9-012.000', 'phone' => '021-57958822'],
                ['name' => 'PT Mayora Indah Tbk', 'npwp' => '01.456.789.0-123.000', 'phone' => '021-5655320'],
                ['name' => 'PT Nestle Indonesia', 'npwp' => '01.567.890.1-234.000', 'phone' => '021-78836000'],
                ['name' => 'PT Sumber Alfaria Trijaya Tbk', 'npwp' => '01.678.901.2-345.000', 'phone' => '021-55755960'],
            ],
        ];
    }

    protected function loadData(): array
    {
        if (File::exists($this->dataFile)) {
            try {
                $decoded = json_decode(File::get($this->dataFile), true);
                if (is_array($decoded)) {
                    $defaults = self::getDefaultMasterData();
                    return array_merge($defaults, $decoded);
                }
            } catch (\Exception $e) {
                // fallback
            }
        }

        $defaults = self::getDefaultMasterData();
        $this->saveData($defaults);
        return $defaults;
    }

    protected function saveData(array $data): void
    {
        $dir = dirname($this->dataFile);
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        File::put($this->dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => $this->loadData(),
        ]);
    }

    public function update(Request $request)
    {
        $payload = $request->validate([
            'categories' => 'nullable|array',
            'brands' => 'nullable|array',
            'companies' => 'nullable|array',
            'warehouses' => 'nullable|array',
            'suppliers' => 'nullable|array',
            'payment_statuses' => 'nullable|array',
            'ppn_rules' => 'nullable|array',
        ]);

        $current = $this->loadData();
        foreach ($payload as $key => $val) {
            if (is_array($val)) {
                $current[$key] = $val;
            }
        }

        $this->saveData($current);

        return response()->json([
            'success' => true,
            'message' => 'Data master berhasil diperbarui.',
            'data' => $current,
        ]);
    }

    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:categories,brands,companies,warehouses,suppliers,payment_statuses,ppn_rules',
            'item' => 'required',
        ]);

        $type = $validated['type'];
        $item = $validated['item'];
        $current = $this->loadData();

        // Special case: ppn_rules receives the full array directly
        if ($type === 'ppn_rules') {
            $rules = is_array($item) ? array_values(array_map('trim', $item)) : [];
            $current['ppn_rules'] = $rules;
            $this->saveData($current);
            return response()->json([
                'success' => true,
                'message' => 'Aturan PPN berhasil diperbarui.',
                'data' => $current,
            ]);
        }

        if (in_array($type, ['categories', 'brands', 'companies', 'payment_statuses'])) {
            $name = trim(is_string($item) ? $item : ($item['name'] ?? ''));
            if ($name === '') {
                return response()->json(['success' => false, 'message' => 'Nama tidak boleh kosong.'], 422);
            }
            if (!isset($current[$type]) || !is_array($current[$type])) {
                $current[$type] = [];
            }
            // Check duplicate case-insensitively
            $exists = false;
            foreach ($current[$type] as $existing) {
                if (strcasecmp(trim($existing), $name) === 0) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $current[$type][] = $name;
            }
        } elseif ($type === 'warehouses') {
            $code = strtoupper(trim(is_array($item) ? ($item['code'] ?? '') : (string)$item));
            $name = trim(is_array($item) ? ($item['name'] ?? '') : '');
            $location = trim(is_array($item) ? ($item['location'] ?? '') : '');
            if ($code === '') {
                return response()->json(['success' => false, 'message' => 'Kode gudang tidak boleh kosong.'], 422);
            }
            $exists = false;
            foreach ($current['warehouses'] as &$w) {
                if (strcasecmp($w['code'], $code) === 0) {
                    $w['name'] = $name ?: $w['name'];
                    $w['location'] = $location ?: ($w['location'] ?? '');
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $current['warehouses'][] = [
                    'code' => $code,
                    'name' => $name ?: $code,
                    'location' => $location,
                ];
            }
        } elseif ($type === 'suppliers') {
            $name = trim(is_array($item) ? ($item['name'] ?? '') : (string)$item);
            $npwp = trim(is_array($item) ? ($item['npwp'] ?? '') : '');
            $phone = trim(is_array($item) ? ($item['phone'] ?? '') : '');
            if ($name === '') {
                return response()->json(['success' => false, 'message' => 'Nama supplier tidak boleh kosong.'], 422);
            }
            $exists = false;
            foreach ($current['suppliers'] as &$s) {
                if (strcasecmp($s['name'], $name) === 0) {
                    $s['npwp'] = $npwp ?: ($s['npwp'] ?? '');
                    $s['phone'] = $phone ?: ($s['phone'] ?? '');
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $current['suppliers'][] = [
                    'name' => $name,
                    'npwp' => $npwp,
                    'phone' => $phone,
                ];
            }
        }

        $this->saveData($current);

        return response()->json([
            'success' => true,
            'message' => 'Item data master berhasil ditambahkan.',
            'data' => $current,
        ]);
    }

    public function updateItem(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:categories,brands,companies,warehouses,suppliers,payment_statuses,ppn_rules',
            'oldValue' => 'required',
            'item' => 'required',
        ]);

        $type = $validated['type'];
        $oldValue = $validated['oldValue'];
        $item = $validated['item'];
        $current = $this->loadData();

        if (in_array($type, ['categories', 'brands', 'companies', 'payment_statuses'])) {
            $oldName = trim(is_string($oldValue) ? $oldValue : ($oldValue['name'] ?? ''));
            $newName = trim(is_string($item) ? $item : ($item['name'] ?? ''));
            if ($newName === '') {
                return response()->json(['success' => false, 'message' => 'Nama tidak boleh kosong.'], 422);
            }
            if (!isset($current[$type]) || !is_array($current[$type])) {
                $current[$type] = [];
            }
            $updated = false;
            foreach ($current[$type] as $i => $val) {
                if (strcasecmp(trim($val), $oldName) === 0) {
                    $current[$type][$i] = $newName;
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $current[$type][] = $newName;
            }
        } elseif ($type === 'warehouses') {
            $oldCode = strtoupper(trim(is_array($oldValue) ? ($oldValue['code'] ?? '') : (string)$oldValue));
            $newCode = strtoupper(trim(is_array($item) ? ($item['code'] ?? '') : (string)$item));
            $newName = trim(is_array($item) ? ($item['name'] ?? '') : '');
            $newLocation = trim(is_array($item) ? ($item['location'] ?? '') : '');
            if ($newCode === '') {
                return response()->json(['success' => false, 'message' => 'Kode gudang tidak boleh kosong.'], 422);
            }
            $updated = false;
            foreach ($current['warehouses'] as $i => $w) {
                if (strcasecmp($w['code'], $oldCode) === 0) {
                    $current['warehouses'][$i] = [
                        'code' => $newCode,
                        'name' => $newName ?: $newCode,
                        'location' => $newLocation,
                    ];
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $current['warehouses'][] = [
                    'code' => $newCode,
                    'name' => $newName ?: $newCode,
                    'location' => $newLocation,
                ];
            }
        } elseif ($type === 'suppliers') {
            $oldName = trim(is_array($oldValue) ? ($oldValue['name'] ?? '') : (string)$oldValue);
            $newName = trim(is_array($item) ? ($item['name'] ?? '') : (string)$item);
            $npwp = trim(is_array($item) ? ($item['npwp'] ?? '') : '');
            $phone = trim(is_array($item) ? ($item['phone'] ?? '') : '');
            if ($newName === '') {
                return response()->json(['success' => false, 'message' => 'Nama supplier tidak boleh kosong.'], 422);
            }
            $updated = false;
            foreach ($current['suppliers'] as $i => $s) {
                if (strcasecmp($s['name'], $oldName) === 0) {
                    $current['suppliers'][$i] = [
                        'name' => $newName,
                        'npwp' => $npwp,
                        'phone' => $phone,
                    ];
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $current['suppliers'][] = [
                    'name' => $newName,
                    'npwp' => $npwp,
                    'phone' => $phone,
                ];
            }
        }

        $this->saveData($current);

        return response()->json([
            'success' => true,
            'message' => 'Item data master berhasil diperbarui.',
            'data' => $current,
        ]);
    }

    public function deleteItem(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:categories,brands,companies,warehouses,suppliers,payment_statuses,ppn_rules',
            'value' => 'required|string',
        ]);

        $type = $validated['type'];
        $val = trim($validated['value']);
        $current = $this->loadData();

        if (in_array($type, ['categories', 'brands', 'companies', 'payment_statuses'])) {
            $current[$type] = array_values(array_filter($current[$type] ?? [], function ($item) use ($val) {
                return strcasecmp(trim($item), $val) !== 0;
            }));
        } elseif ($type === 'warehouses') {
            $current['warehouses'] = array_values(array_filter($current['warehouses'], function ($w) use ($val) {
                return strcasecmp(trim($w['code']), $val) !== 0 && strcasecmp(trim($w['name'] ?? ''), $val) !== 0;
            }));
        } elseif ($type === 'suppliers') {
            $current['suppliers'] = array_values(array_filter($current['suppliers'], function ($s) use ($val) {
                return strcasecmp(trim($s['name']), $val) !== 0;
            }));
        }

        $this->saveData($current);

        return response()->json([
            'success' => true,
            'message' => 'Item data master berhasil dihapus.',
            'data' => $current,
        ]);
    }

    public function reset()
    {
        $defaults = self::getDefaultMasterData();
        $this->saveData($defaults);

        return response()->json([
            'success' => true,
            'message' => 'Data master berhasil dikembalikan ke default.',
            'data' => $defaults,
        ]);
    }
}
