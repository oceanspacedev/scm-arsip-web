<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramDocument;
use App\Models\RawImport;
use App\Models\User;
use Database\Seeders\ScmDataSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AdminSettingController extends Controller
{
    protected string $settingsFile;

    public function __construct()
    {
        $this->settingsFile = storage_path('app/settings.json');
    }

    public static function getDefaultPermissions(): array
    {
        return [
            'Admin SCM' => [
                'view_dashboard' => true,
                'view_programs' => true,
                'view_master_columns' => true,
                'view_finance_columns' => true,
                'add_program' => true,
                'edit_purchase' => true,
                'edit_finance' => true,
                'delete_program' => true,
                'import_program' => true,
                'export_program' => true,
                'upload_memo' => true,
                'upload_invoice' => true,
                'upload_faktur' => true,
                'delete_memo' => true,
                'delete_finance_doc' => true,
            ],
            'Staff SCM' => [
                'view_dashboard' => true,
                'view_programs' => true,
                'view_master_columns' => true,
                'view_finance_columns' => true,
                'add_program' => true,
                'edit_purchase' => true,
                'edit_finance' => true,
                'delete_program' => false,
                'import_program' => true,
                'export_program' => true,
                'upload_memo' => true,
                'upload_invoice' => true,
                'upload_faktur' => true,
                'delete_memo' => true,
                'delete_finance_doc' => true,
            ],
            'Staff Gudang' => [
                'view_dashboard' => true,
                'view_programs' => true,
                'view_master_columns' => true,
                'view_finance_columns' => false,
                'add_program' => false,
                'edit_purchase' => false,
                'edit_finance' => false,
                'delete_program' => false,
                'import_program' => false,
                'export_program' => false,
                'upload_memo' => true,
                'upload_invoice' => false,
                'upload_faktur' => false,
                'delete_memo' => true,
                'delete_finance_doc' => false,
            ],
            'Staff Finance' => [
                'view_dashboard' => true,
                'view_programs' => true,
                'view_master_columns' => true,
                'view_finance_columns' => true,
                'add_program' => false,
                'edit_purchase' => false,
                'edit_finance' => true,
                'delete_program' => false,
                'import_program' => false,
                'export_program' => false,
                'upload_memo' => false,
                'upload_invoice' => true,
                'upload_faktur' => true,
                'delete_memo' => false,
                'delete_finance_doc' => true,
            ],
        ];
    }

    /**
     * Read system settings from JSON file
     */
    protected function getSettingsData(): array
    {
        $defaultSettings = [
            'show_demo_accounts' => true,
            'tax_rate_standard' => 11,
            'faktur_validation' => true,
            'role_permissions' => self::getDefaultPermissions(),
        ];

        if (File::exists($this->settingsFile)) {
            try {
                $content = File::get($this->settingsFile);
                $data = json_decode($content, true);
                if (is_array($data)) {
                    return array_merge($defaultSettings, $data);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed reading settings file: ' . $e->getMessage());
            }
        }

        return $defaultSettings;
    }

    /**
     * Save system settings to JSON file
     */
    protected function saveSettingsData(array $settings): void
    {
        $dir = dirname($this->settingsFile);
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        File::put($this->settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * GET /api/settings
     * Public endpoint to get global settings (e.g. show_demo_accounts for login screen)
     */
    public function getPublicSettings()
    {
        $settings = $this->getSettingsData();

        return response()->json([
            'success' => true,
            'settings' => [
                'show_demo_accounts' => (bool) ($settings['show_demo_accounts'] ?? true),
                'tax_rate_standard' => (int) ($settings['tax_rate_standard'] ?? 11),
            ]
        ]);
    }

    /**
     * GET /api/admin/role-permissions
     * Ambil data matriks hak akses per role
     */
    public function getRolePermissions()
    {
        $settings = $this->getSettingsData();
        $defaults = self::getDefaultPermissions();
        $permissions = $settings['role_permissions'] ?? $defaults;

        foreach ($defaults as $role => $keys) {
            if (!isset($permissions[$role])) {
                $permissions[$role] = $keys;
            } else {
                $permissions[$role] = array_merge($keys, $permissions[$role]);
            }
        }

        return response()->json([
            'success' => true,
            'permissions' => $permissions,
            'defaults' => $defaults,
        ]);
    }

    /**
     * POST /api/admin/role-permissions
     * Simpan perubahan matriks hak akses role
     */
    public function updateRolePermissions(Request $request)
    {
        $permissions = $request->input('permissions', []);
        if (!is_array($permissions) || empty($permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'Format data role permission tidak valid.'
            ], 422);
        }

        $settings = $this->getSettingsData();
        $defaults = self::getDefaultPermissions();

        $saved = [];
        foreach ($defaults as $role => $keys) {
            $saved[$role] = [];
            foreach ($keys as $k => $defaultVal) {
                if (isset($permissions[$role][$k])) {
                    $saved[$role][$k] = (bool) $permissions[$role][$k];
                } else {
                    $saved[$role][$k] = $defaultVal;
                }
            }
        }

        $settings['role_permissions'] = $saved;
        $this->saveSettingsData($settings);

        return response()->json([
            'success' => true,
            'message' => 'Hak akses dan perizinan role berhasil disimpan.',
            'permissions' => $saved
        ]);
    }

    /**
     * POST /api/admin/settings
     * Update settings by Admin
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'show_demo_accounts' => 'nullable|boolean',
            'tax_rate_standard' => 'nullable|numeric',
        ]);

        $settings = $this->getSettingsData();

        if ($request->has('show_demo_accounts')) {
            $settings['show_demo_accounts'] = (bool) $request->input('show_demo_accounts');
        }

        if ($request->has('tax_rate_standard')) {
            $settings['tax_rate_standard'] = (int) $request->input('tax_rate_standard');
        }

        $this->saveSettingsData($settings);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan sistem berhasil diperbarui.',
            'settings' => $settings
        ]);
    }

    /**
     * POST /api/admin/reset-data
     * Hapus semua data program, dokumen, dan arsip secara total (wipe clean)
     */
    public function resetData()
    {
        try {
            // 1. Hapus SEMUA berkas dokumen lampiran program
            ProgramDocument::query()->delete();

            // 2. Hapus SEMUA data arsip program
            Program::query()->delete();

            // 3. Hapus SEMUA riwayat file import mentah
            RawImport::query()->delete();

            // 4. Hapus user pendaftar tambahan (pertahankan akun admin & akun demo standar agar tidak terkunci)
            User::whereNotIn('email', [
                'admin@scm.corp',
                'auditor@pajak.corp',
                'staff@scm.corp'
            ])->delete();

            return response()->json([
                'success' => true,
                'message' => 'Seluruh data arsip program, dokumen, dan riwayat impor telah berhasil dihapus bersih.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Reset data failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mereset data: ' . $e->getMessage()
            ], 500);
        }
    }
}
