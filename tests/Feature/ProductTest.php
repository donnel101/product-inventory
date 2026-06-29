<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => "Bearer $token"];
    }

    public function test_can_create_product()
    {
        $headers = $this->authenticate();

        $data = [
            'name' => 'Laptop',
            'sku' => 'LAP-123',
            'price' => 999.99,
            'stock' => 10,
        ];

        $response = $this->postJson('/api/products', $data, $headers);
        $response->assertStatus(201)
                 ->assertJsonFragment(['sku' => 'LAP-123']);
    }

    public function test_sku_must_be_unique()
    {
        $headers = $this->authenticate();
        Product::factory()->create(['sku' => 'LAP-123']);

        $response = $this->postJson('/api/products', [
            'name' => 'Another',
            'sku' => 'LAP-123',
            'price' => 100,
            'stock' => 1,
        ], $headers);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('sku');
    }

    // Add tests for update, delete, search/filter, etc.
}