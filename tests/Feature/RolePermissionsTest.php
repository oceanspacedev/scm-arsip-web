<?php

namespace Tests\Feature;

use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    public function test_can_get_role_permissions(): void
    {
        $response = $this->getJson('/api/admin/role-permissions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'permissions' => [
                'Admin SCM',
                'Staff SCM',
                'Staff Gudang',
                'Staff Finance',
            ],
            'defaults'
        ]);

        $data = $response->json();
        $this->assertTrue($data['permissions']['Admin SCM']['view_dashboard']);
        $this->assertTrue($data['permissions']['Staff SCM']['edit_purchase']);
        $this->assertFalse($data['permissions']['Staff Gudang']['add_program']);
        $this->assertTrue($data['permissions']['Staff Finance']['edit_finance']);
    }

    public function test_can_update_role_permissions(): void
    {
        $getResp = $this->getJson('/api/admin/role-permissions');
        $permissions = $getResp->json('permissions');

        // Toggle a permission for Staff Gudang
        $permissions['Staff Gudang']['import_program'] = true;

        $updateResp = $this->postJson('/api/admin/role-permissions', [
            'permissions' => $permissions
        ]);

        $updateResp->assertStatus(200);
        $updateResp->assertJson([
            'success' => true
        ]);

        // Verify updated
        $verifyResp = $this->getJson('/api/admin/role-permissions');
        $this->assertTrue($verifyResp->json('permissions.Staff Gudang.import_program'));

        // Revert back
        $permissions['Staff Gudang']['import_program'] = false;
        $this->postJson('/api/admin/role-permissions', [
            'permissions' => $permissions
        ]);
    }
}
