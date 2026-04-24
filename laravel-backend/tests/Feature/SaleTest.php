<?php

namespace Tests\Feature;

use Database\Factories\ClientFactory;
use Database\Factories\ProductFactory;
use Database\Factories\SaleFactory;
use Database\Factories\ServiceFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): static
    {
        return $this->actingAs(UserFactory::new()->create(), 'sanctum');
    }

    // --- index ---

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/sales')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_index_returns_all_sales_without_filters(): void
    {
        SaleFactory::new()->count(3)->create();

        $response = $this->actingAsUser()->getJson('/api/sales');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Sales retrieved successfully.')
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'client_id', 'client', 'daily_sequence', 'total', 'created_at', 'details'],
                ],
            ]);
    }

    public function test_index_filters_by_client_id(): void
    {
        $targetClient = ClientFactory::new()->create();
        SaleFactory::new()->count(2)->for($targetClient)->create();
        SaleFactory::new()->count(3)->create();

        $response = $this->actingAsUser()->getJson("/api/sales?client_id={$targetClient->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.client_id', $targetClient->id)
            ->assertJsonPath('data.1.client_id', $targetClient->id);
    }

    public function test_index_filters_by_date_from(): void
    {
        SaleFactory::new()->onDate('2024-01-10')->create();
        SaleFactory::new()->onDate('2024-03-15')->create();
        SaleFactory::new()->onDate('2024-06-01')->create();

        $response = $this->actingAsUser()->getJson('/api/sales?date_from=2024-03-01');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_filters_by_date_to(): void
    {
        SaleFactory::new()->onDate('2024-01-10')->create();
        SaleFactory::new()->onDate('2024-03-15')->create();
        SaleFactory::new()->onDate('2024-06-01')->create();

        $response = $this->actingAsUser()->getJson('/api/sales?date_to=2024-03-31');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_filters_by_date_range(): void
    {
        SaleFactory::new()->onDate('2024-01-10')->create();
        SaleFactory::new()->onDate('2024-03-15')->create();
        SaleFactory::new()->onDate('2024-06-01')->create();

        $response = $this->actingAsUser()->getJson('/api/sales?date_from=2024-02-01&date_to=2024-05-31');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_filters_by_client_and_date_range(): void
    {
        $targetClient = ClientFactory::new()->create();
        SaleFactory::new()->for($targetClient)->onDate('2024-03-10')->create();
        SaleFactory::new()->for($targetClient)->onDate('2024-07-20')->create();
        SaleFactory::new()->onDate('2024-03-10')->create();

        $response = $this->actingAsUser()
            ->getJson("/api/sales?client_id={$targetClient->id}&date_from=2024-01-01&date_to=2024-06-30");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.client_id', $targetClient->id);
    }

    public function test_index_returns_422_for_nonexistent_client_id(): void
    {
        $response = $this->actingAsUser()->getJson('/api/sales?client_id=999999');

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_index_returns_422_for_invalid_date_format(): void
    {
        $response = $this->actingAsUser()->getJson('/api/sales?date_from=15-03-2024');

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_index_returns_sales_ordered_by_created_at_desc(): void
    {
        SaleFactory::new()->onDate('2024-01-01')->create();
        SaleFactory::new()->onDate('2024-06-15')->create();
        SaleFactory::new()->onDate('2024-03-20')->create();

        $response = $this->actingAsUser()->getJson('/api/sales');

        $response->assertOk();
        $dates = collect($response->json('data'))->pluck('created_at')->toArray();
        $this->assertEquals($dates, collect($dates)->sortDesc()->values()->toArray());
    }

    public function test_index_returns_pagination_meta(): void
    {
        SaleFactory::new()->count(3)->create();

        $response = $this->actingAsUser()->getJson('/api/sales');

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_index_paginates_correctly(): void
    {
        SaleFactory::new()->count(20)->create();

        $page1 = $this->actingAsUser()->getJson('/api/sales?per_page=15');
        $page1->assertOk()->assertJsonCount(15, 'data');
        $this->assertEquals(1, $page1->json('meta.current_page'));
        $this->assertEquals(2, $page1->json('meta.last_page'));

        $page2 = $this->actingAsUser()->getJson('/api/sales?per_page=15&page=2');
        $page2->assertOk()->assertJsonCount(5, 'data');
        $this->assertEquals(2, $page2->json('meta.current_page'));
    }

    public function test_index_accepts_per_page_parameter(): void
    {
        SaleFactory::new()->count(10)->create();

        $response = $this->actingAsUser()->getJson('/api/sales?per_page=3');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 3)
            ->assertJsonPath('meta.total', 10)
            ->assertJsonPath('meta.last_page', 4);
    }

    // --- store ---

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/sales', [])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_store_creates_sale_and_returns_correct_json_structure(): void
    {
        $client = ClientFactory::new()->create();
        $product = ProductFactory::new()->create(['stock' => 5, 'price' => 100.00]);

        $response = $this->actingAsUser()->postJson('/api/sales', [
            'client_id' => $client->id,
            'items' => [
                ['product_id' => $product->id, 'service_id' => null, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Sale processed successfully.')
            ->assertJsonStructure([
                'success', 'message', 'meta',
                'data' => ['id', 'client_id', 'daily_sequence', 'total', 'created_at', 'details'],
            ]);

        $response->assertJsonPath('data.total', '200.00');
        $response->assertJsonPath('data.daily_sequence', 1);
    }

    public function test_store_returns_422_when_product_is_out_of_stock(): void
    {
        $client = ClientFactory::new()->create();
        $product = ProductFactory::new()->outOfStock()->create();

        $response = $this->actingAsUser()->postJson('/api/sales', [
            'client_id' => $client->id,
            'items' => [
                ['product_id' => $product->id, 'service_id' => null, 'quantity' => 1],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'INSUFFICIENT_STOCK');
    }

    public function test_store_returns_422_when_service_is_disabled(): void
    {
        $client = ClientFactory::new()->create();
        $service = ServiceFactory::new()->disabled()->create();

        $response = $this->actingAsUser()->postJson('/api/sales', [
            'client_id' => $client->id,
            'items' => [
                ['product_id' => null, 'service_id' => $service->id, 'quantity' => 1],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'SERVICE_DEPENDENCY_FAILED');
    }

    public function test_store_returns_422_for_missing_required_fields(): void
    {
        $response = $this->actingAsUser()->postJson('/api/sales', []);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    public function test_store_returns_422_when_item_has_both_product_id_and_service_id_null(): void
    {
        $client = ClientFactory::new()->create();

        $response = $this->actingAsUser()->postJson('/api/sales', [
            'client_id' => $client->id,
            'items' => [
                ['product_id' => null, 'service_id' => null, 'quantity' => 1],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }
}
