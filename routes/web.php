<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\AdminSettingController;
use App\Http\Controllers\Api\DocumentUploadLogController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\WhmsIntegrationController;
use App\Http\Middleware\EnsureWhmsIntegrationToken;

/*
|--------------------------------------------------------------------------
| SCM TaxVault API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('api')->group(function () {
    // 1. Authentication & WhatsApp OTP
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/auth/validate-password', [AuthController::class, 'validatePassword']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Backward compatibility for direct WhatsApp OTP
    Route::post('/whatsapp/send-otp', [AuthController::class, 'sendOtp']);

    // 2. Admin User Management & Approval (ACC Admin)
    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::post('/admin/users', [AdminUserController::class, 'store']);
    Route::put('/admin/users/{id}', [AdminUserController::class, 'update']);
    Route::post('/admin/users/{id}/approve', [AdminUserController::class, 'approve']);
    Route::post('/admin/users/{id}/reject', [AdminUserController::class, 'reject']);
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy']);

    // 3. Tax Programs & Documents Management
    Route::get('/programs', [ProgramController::class, 'index']);
    Route::post('/programs', [ProgramController::class, 'store']);
    Route::get('/programs/{id}', [ProgramController::class, 'show']);
    Route::put('/programs/{id}', [ProgramController::class, 'update']);
    Route::delete('/programs/{id}', [ProgramController::class, 'destroy']);
    Route::post('/programs/{id}/documents', [ProgramController::class, 'uploadDocument']);
    Route::get('/programs/{id}/documents/{docId}/file', [ProgramController::class, 'documentFile']);
    Route::post('/programs/{id}/documents/{docId}/analyze', [ProgramController::class, 'analyzeDocumentAi']);
    Route::delete('/programs/{id}/documents/{docId}', [ProgramController::class, 'deleteDocument'])->where('docId', '.*');
    Route::post('/programs/import', [ProgramController::class, 'import']);
    Route::post('/documents/auto-upload', [ProgramController::class, 'autoUploadDocument']);
    Route::get('/document-logs', [DocumentUploadLogController::class, 'index']);
    Route::post('/document-logs/{id}/skip', [DocumentUploadLogController::class, 'skip']);
    Route::get('/programs/raw-imports', [ProgramController::class, 'rawImports']);
    Route::get('/programs/raw-imports/{id}/download', [ProgramController::class, 'downloadRawImport']);
    Route::delete('/programs/raw-imports/{id}', [ProgramController::class, 'deleteRawImport']);

    // 4. System Settings, Role Permissions & Reset Data (Admin Controls)
    Route::get('/settings', [AdminSettingController::class, 'getPublicSettings']);
    Route::post('/admin/settings', [AdminSettingController::class, 'updateSettings']);
    Route::get('/admin/role-permissions', [AdminSettingController::class, 'getRolePermissions']);
    Route::post('/admin/role-permissions', [AdminSettingController::class, 'updateRolePermissions']);

    // 5. Master Data Management (Kategori, Brand, Company, Gudang, Supplier)
    Route::get('/master-data', [MasterDataController::class, 'index']);
    Route::post('/master-data', [MasterDataController::class, 'update']);
    Route::post('/master-data/item', [MasterDataController::class, 'addItem']);
    Route::post('/master-data/update-item', [MasterDataController::class, 'updateItem']);
    Route::post('/master-data/delete-item', [MasterDataController::class, 'deleteItem']);
    Route::post('/master-data/reset', [MasterDataController::class, 'reset']);

    // 6. Integrasi WHMS (server ke server): Memo/DO dari petugas gudang, dilindungi token
    Route::prefix('integrations/whms')->middleware(EnsureWhmsIntegrationToken::class)->group(function () {
        Route::get('/programs', [WhmsIntegrationController::class, 'programs']);
        Route::post('/documents', [WhmsIntegrationController::class, 'storeDocument']);
    });
});

/*
|--------------------------------------------------------------------------
| SPA Catch-All Route (Vue 3 frontend)
|--------------------------------------------------------------------------
*/
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
