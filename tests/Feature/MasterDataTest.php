<?php

namespace Tests\Feature;

use Tests\TestCase;

class MasterDataTest extends TestCase
{
    public function test_can_get_master_data(): void
    {
        $response = $this->getJson('/api/master-data');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'categories',
                'brands',
                'companies',
                'warehouses',
                'suppliers',
                'payment_statuses',
            ]
        ]);
        $this->assertContains('PT SCM Nusantara', $response->json('data.companies'));
        $this->assertContains('Waiting Payment', $response->json('data.payment_statuses'));
    }

    public function test_can_add_and_delete_master_data_item(): void
    {
        // Add brand
        $addResponse = $this->postJson('/api/master-data/item', [
            'type' => 'brands',
            'item' => 'Test Brand XYZ',
        ]);
        $addResponse->assertStatus(200);
        $this->assertContains('Test Brand XYZ', $addResponse->json('data.brands'));

        // Delete brand
        $delResponse = $this->postJson('/api/master-data/delete-item', [
            'type' => 'brands',
            'value' => 'Test Brand XYZ',
        ]);
        $delResponse->assertStatus(200);
        $this->assertNotContains('Test Brand XYZ', $delResponse->json('data.brands'));

        // Add payment status
        $addStatus = $this->postJson('/api/master-data/item', [
            'type' => 'payment_statuses',
            'item' => 'Termin 50%',
        ]);
        $addStatus->assertStatus(200);
        $this->assertContains('Termin 50%', $addStatus->json('data.payment_statuses'));

        // Delete payment status
        $delStatus = $this->postJson('/api/master-data/delete-item', [
            'type' => 'payment_statuses',
            'value' => 'Termin 50%',
        ]);
        $delStatus->assertStatus(200);
        $this->assertNotContains('Termin 50%', $delStatus->json('data.payment_statuses'));

        // Update item test
        $addCat = $this->postJson('/api/master-data/item', [
            'type' => 'categories',
            'item' => 'Category Old Name',
        ]);
        $addCat->assertStatus(200);

        $updCat = $this->postJson('/api/master-data/update-item', [
            'type' => 'categories',
            'oldValue' => 'Category Old Name',
            'item' => 'Category New Name',
        ]);
        $updCat->assertStatus(200);
        $this->assertContains('Category New Name', $updCat->json('data.categories'));
        $this->assertNotContains('Category Old Name', $updCat->json('data.categories'));

        // Cleanup
        $this->postJson('/api/master-data/delete-item', [
            'type' => 'categories',
            'value' => 'Category New Name',
        ]);
    }
}
