<?php

namespace Tests\Feature;

use Database\Factories\ProductFactory;
use Database\Factories\SaleDetailFactory;
use Database\Factories\SaleFactory;
use Database\Factories\ServiceFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): static
    {
        return $this->actingAs(UserFactory::new()->create(), 'sanctum');
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_index_returns_correct_json_structure(): void
    {
        $response = $this->actingAsUser()->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dashboard data retrieved successfully.')
            ->assertJsonStructure([
                'success',
                'message',
                'meta',
                'data' => ['products_stock', 'sales_by_month', 'sales_by_product', 'sales_by_service'],
            ]);
    }

    public function test_products_stock_returns_all_products_ordered_by_stock_desc(): void
    {
        ProductFactory::new()->create(['name' => 'Low Stock Item', 'stock' => 5]);
        ProductFactory::new()->create(['name' => 'High Stock Item', 'stock' => 100]);
        ProductFactory::new()->create(['name' => 'Mid Stock Item', 'stock' => 50]);

        $response = $this->actingAsUser()->getJson('/api/dashboard');

        $response->assertOk();

        $productsStock = $response->json('data.products_stock');

        $this->assertCount(3, $productsStock);
        $this->assertEquals('High Stock Item', $productsStock[0]['name']);
        $this->assertEquals(100, $productsStock[0]['stock']);
        $this->assertEquals('Mid Stock Item', $productsStock[1]['name']);
        $this->assertEquals('Low Stock Item', $productsStock[2]['name']);
    }

    public function test_sales_by_month_groups_sales_correctly(): void
    {
        SaleFactory::new()->onDate('2025-01-15')->create(['total' => 100.00]);
        SaleFactory::new()->onDate('2025-01-28')->create(['total' => 200.00]);
        SaleFactory::new()->onDate('2025-03-10')->create(['total' => 50.00]);

        $response = $this->actingAsUser()->getJson('/api/dashboard');

        $response->assertOk();

        $byMonth = collect($response->json('data.sales_by_month'))->keyBy('month');

        $this->assertCount(2, $byMonth);
        $this->assertEquals(300.00, $byMonth['2025-01']['total']);
        $this->assertEquals(2, $byMonth['2025-01']['count']);
        $this->assertEquals(50.00, $byMonth['2025-03']['total']);
        $this->assertEquals(1, $byMonth['2025-03']['count']);
        $this->assertEquals('Jan 2025', $byMonth['2025-01']['label']);
    }

    public function test_sales_by_product_aggregates_sale_detail_totals(): void
    {
        $productA = ProductFactory::new()->create(['name' => 'Product A']);
        $productB = ProductFactory::new()->create(['name' => 'Product B']);

        SaleDetailFactory::new()->create(['product_id' => $productA->id, 'service_id' => null, 'quantity' => 2, 'unit_price' => 100.00]);
        SaleDetailFactory::new()->create(['product_id' => $productA->id, 'service_id' => null, 'quantity' => 3, 'unit_price' => 50.00]);
        SaleDetailFactory::new()->create(['product_id' => $productB->id, 'service_id' => null, 'quantity' => 1, 'unit_price' => 400.00]);

        $response = $this->actingAsUser()->getJson('/api/dashboard');

        $response->assertOk();

        $byProduct = collect($response->json('data.sales_by_product'))->keyBy('name');

        $this->assertArrayHasKey('Product A', $byProduct->toArray());
        $this->assertArrayHasKey('Product B', $byProduct->toArray());
        $this->assertEquals(350.00, $byProduct['Product A']['total']);
        $this->assertEquals(400.00, $byProduct['Product B']['total']);

        $names = collect($response->json('data.sales_by_product'))->pluck('name')->toArray();
        $this->assertEquals(['Product B', 'Product A'], $names);
    }

    public function test_sales_by_service_aggregates_sale_detail_totals(): void
    {
        $serviceA = ServiceFactory::new()->create(['name' => 'Service A']);
        $serviceB = ServiceFactory::new()->create(['name' => 'Service B']);

        SaleDetailFactory::new()->create(['product_id' => null, 'service_id' => $serviceA->id, 'quantity' => 2, 'unit_price' => 150.00]);
        SaleDetailFactory::new()->create(['product_id' => null, 'service_id' => $serviceB->id, 'quantity' => 5, 'unit_price' => 80.00]);

        $response = $this->actingAsUser()->getJson('/api/dashboard');

        $response->assertOk();

        $byService = collect($response->json('data.sales_by_service'))->keyBy('name');

        $this->assertArrayHasKey('Service A', $byService->toArray());
        $this->assertArrayHasKey('Service B', $byService->toArray());
        $this->assertEquals(300.00, $byService['Service A']['total']);
        $this->assertEquals(400.00, $byService['Service B']['total']);

        $names = collect($response->json('data.sales_by_service'))->pluck('name')->toArray();
        $this->assertEquals(['Service B', 'Service A'], $names);
    }
}
