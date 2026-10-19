<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
     * Public endpoint to get global settings
     */
    public function getPublicSettings()
    {
        $settings = $this->getSettingsData();

        return response()->json([
            'success' => true,
            'settings' => [
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
            'tax_rate_standard' => 'nullable|numeric',
        ]);

        $settings = $this->getSettingsData();

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
}
