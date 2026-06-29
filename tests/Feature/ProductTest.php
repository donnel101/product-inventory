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
        $user = $this->createUser();
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => "Bearer $token"];
    }

    protected function createUser()
    {
        return User::factory()->create();
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

    public function test_product_can_be_updated_successfully(): void
    {
        $headers = $this->authenticate();
        // 1. Create a product in the test database using your factory
        $product = Product::factory()->create([
            'name' => 'Old Product Name',
            'sku' => 'OLD-1111',
            'price' => 50.00,
            'stock' => 5
        ]);

        // 2. Define the new data payload
        $updatedData = [
            'name' => 'Updated Product Name',
            'sku' => 'NEW-2222',
            'description' => 'This is an updated description',
            'price' => 123.12,
            'stock' => 12
        ];

        // 3. Send a PUT request to the route with the product ID
        // We use putJson to guarantee the Accept: application/json header is set
        $response = $this->putJson("/api/products/{$product->id}", $updatedData, $headers);

        // 4. Assert that the request was successful (200 OK)
        $response->assertStatus(200);

        // 5. Assert the JSON response contains the ProductResource structure and new data
        $response->assertJson([
            'data' => [
                'id' => $product->id,
                'name' => 'Updated Product Name',
                'sku' => 'NEW-2222',
                'price' => 123.12,
                'stock' => 12
            ]
        ]);

        // 6. Double check that the change actually saved in the actual database table
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'sku' => 'NEW-2222'
        ]);
    }

    /**
     * Test updating a product that does not exist.
     */
    public function test_updating_non_existent_product_returns_404(): void
    {
        $headers = $this->authenticate();

        $payload = [
            'name' => 'Sample Product',
            'sku' => 'AAA-0000',
            'price' => 10.00,
            'stock' => 10
        ];

        // Send a PUT request to an ID that definitely does not exist (e.g., 999)
        $response = $this->putJson('/api/products/999', $payload, $headers);

        // Assert that the status is 404 Not Found
        $response->assertStatus(404);
        
        // Assert the custom message your controller returns
        $response->assertJson([
            'message' => 'Product not Found'
        ]);
    }

    /**
     * Test retrieving a single product successfully.
     */
    public function test_can_retrieve_single_product_successfully(): void
    {
        $headers = $this->authenticate();
        // 1. Create a dummy product in the test database
        $product = Product::factory()->create([
            'name' => 'Specific Test Product',
            'sku' => 'SHOW-1234',
            'price' => 99.99,
            'stock' => 20
        ]);

        // 2. Send a GET request to the specific product ID
        // getJson ensures the Accept: application/json header is automatically added
        $response = $this->getJson("/api/products/{$product->id}",$headers);

        // 3. Assert the response status is 200 OK
        $response->assertStatus(200);

        // 4. Assert the JSON matches your ProductResource structure
        $response->assertJson([
            'data' => [
                'id' => $product->id,
                'name' => 'Specific Test Product',
                'sku' => 'SHOW-1234',
                'price' => 99.99,
                'stock' => 20
            ]
        ]);
    }

    /**
     * Test that retrieving a missing product returns a 404 status code.
     */
    public function test_retrieving_non_existent_product_returns_404(): void
    {
        $headers = $this->authenticate();
        // 1. Request an ID that definitely does not exist in the empty test DB
        $response = $this->getJson('/api/products/9999',$headers);

        // 2. Assert that your custom 404 response works perfectly
        $response->assertStatus(404);

        // 3. Assert that it returns your exact custom error JSON string
        $response->assertJson([
            'message' => 'Product not Found'
        ]);
    }

    /**
     * Test deleting a product successfully.
     */
    public function test_product_can_be_deleted_successfully(): void
    {
        $user = User::factory()->create();
        $headers['X-Custom-Header'] = 'CustomValue';
        // 1. Create a product that will be targeted for deletion
        $product = Product::factory()->create([
            'name' => 'Product to Delete',
            'sku' => 'DEL-9999'
        ]);

        // 2. Send a DELETE request to the product ID URL
        // deleteJson ensures the request headers are pre-set for API formats
        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/products/{$product->id}");

        // 3. Assert that the server responded with a 200 OK status code
        $response->assertStatus(200);

        // 4. Assert that your custom success message JSON is returned
        $response->assertJson([
            'message' => 'Product deleted successfully'
        ]);

        // 5. Double check the database to confirm the row was actually removed
        $this->assertDatabaseMissing('products', [
            'id' => $product->id
        ]);
    }

    /**
     * Test deleting a product that does not exist.
     */
    public function test_deleting_non_existent_product_returns_404(): void
    {
        $user = $this->createUser();
        $headers['X-Custom-Header'] = 'CustomValue';
        // 1. Attempt to delete an ID that is not in the database
        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/products/8888', $headers);

        // 2. Assert that your fallback mechanism catches this and drops a 404
        $response->assertStatus(404);

        // 3. Assert the JSON matches your custom error message format
        $response->assertJson([
            'message' => 'Product not Found'
        ]);
    }

    /**
     * Test retrieving a basic list of products.
     */
    public function test_can_get_list_of_products_successfully(): void
    {
        // 1. Create a dummy user and authenticate
        $user = $this->createUser();
        
        // 2. Create 3 sample products in the isolated test database
        $products = \App\Models\Product::factory()->count(3)->create();

        // 3. Make the authenticated request
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/products');

        // 4. Assert response is 200 OK
        $response->assertStatus(200);

        // 5. Assert that the response data array contains exactly 3 elements
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Test that the product list returns exactly 15 paginated items.
     */
    public function test_product_list_is_paginated_to_15_items(): void
    {
        $user = $this->createUser();
        
        // 1. Create 20 products (5 more than our page size limit)
        \App\Models\Product::factory()->count(20)->create();

        // 2. Make the authenticated request
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/products');

        $response->assertStatus(200);

        // 3. Assert that the 'data' array contains exactly 15 records on page 1
        $response->assertJsonCount(15, 'data');

        // 4. Verify standard Laravel Eloquent pagination structure exists
        // API resources wrap pagination info in 'meta' and 'links' keys automatically
        $response->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total']
        ]);

        // 5. Explicitly assert the pagination values are correct
        $response->assertJsonFragment([
            'current_page' => 1,
            'per_page' => 15,
            'total' => 20
        ]);
    }
}