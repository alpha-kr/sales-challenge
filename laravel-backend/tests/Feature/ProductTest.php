<?php

namespace Tests\Feature;

use Database\Factories\ProductFactory;
use Database\Factories\SaleDetailFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): static
    {
        return $this->actingAs(UserFactory::new()->create(), 'sanctum');
    }

    public function test_index_returns_list_of_products(): void
    {
        ProductFactory::new()->count(2)->create();

        $response = $this->actingAsUser()->getJson('/api/products');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['success', 'data' => [['id', 'name', 'price', 'stock']], 'message', 'meta']);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/products')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_store_creates_a_product(): void
    {
        $response = $this->actingAsUser()->postJson('/api/products', [
            'name' => 'Widget Pro',
            'price' => 29.99,
            'stock' => 50,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Product created successfully.')
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'price', 'stock'], 'message', 'meta']);

        $this->assertDatabaseHas('products', ['name' => 'Widget Pro']);
    }

    public function test_store_returns_422_for_missing_fields(): void
    {
        $response = $this->actingAsUser()->postJson('/api/products', []);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    public function test_show_returns_product(): void
    {
        $product = ProductFactory::new()->create();

        $response = $this->actingAsUser()->getJson("/api/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('message', 'Product retrieved successfully.');
    }

    public function test_show_returns_404_for_nonexistent_product(): void
    {
        $this->actingAsUser()->getJson('/api/products/9999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
    }

    public function test_update_modifies_product(): void
    {
        $product = ProductFactory::new()->create(['stock' => 10]);

        $response = $this->actingAsUser()->putJson("/api/products/{$product->id}", [
            'name' => $product->name,
            'price' => $product->price,
            'stock' => 99,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.stock', 99)
            ->assertJsonPath('message', 'Product updated successfully.');
    }

    public function test_destroy_deletes_product(): void
    {
        $product = ProductFactory::new()->create();

        $this->actingAsUser()->deleteJson("/api/products/{$product->id}")->assertNoContent();

        $this->assertModelMissing($product);
    }

    public function test_destroy_returns_409_when_product_has_sales(): void
    {
        $product = ProductFactory::new()->create();
        SaleDetailFactory::new()->create(['product_id' => $product->id, 'service_id' => null]);

        $this->actingAsUser()->deleteJson("/api/products/{$product->id}")
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'HAS_ACTIVE_SALES');

        $this->assertModelExists($product);
    }
}
