<?php

namespace Tests\Feature\Actions;

use App\Domain\Sales\Actions\ProcessSaleAction;
use App\Domain\Sales\DTOs\CreateSaleData;
use App\Domain\Shared\Enums\ApiErrorCode;
use App\Exceptions\DomainException;
use Database\Factories\ClientFactory;
use Database\Factories\ProductFactory;
use Database\Factories\SaleDetailFactory;
use Database\Factories\SaleFactory;
use Database\Factories\ServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessSaleActionTest extends TestCase
{
    use RefreshDatabase;

    private ProcessSaleAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(ProcessSaleAction::class);
    }

    public function test_creates_sale_with_daily_sequence_one_for_first_purchase(): void
    {
        $client = ClientFactory::new()->create();
        $product = ProductFactory::new()->create(['stock' => 10, 'price' => 50.00]);

        $sale = $this->action->execute(CreateSaleData::from([
            'client_id' => $client->id,
            'items' => [['product_id' => $product->id, 'service_id' => null, 'quantity' => 2]],
        ]));

        $this->assertEquals(1, $sale->daily_sequence);
        $this->assertEquals(100.00, $sale->total);
        $this->assertDatabaseHas('sale_details', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_increments_daily_sequence_for_same_client_same_day(): void
    {
        $client = ClientFactory::new()->create();
        $product = ProductFactory::new()->create(['stock' => 10]);

        $this->action->execute(CreateSaleData::from([
            'client_id' => $client->id,
            'items' => [['product_id' => $product->id, 'service_id' => null, 'quantity' => 1]],
        ]));

        $secondSale = $this->action->execute(CreateSaleData::from([
            'client_id' => $client->id,
            'items' => [['product_id' => $product->id, 'service_id' => null, 'quantity' => 1]],
        ]));

        $this->assertEquals(2, $secondSale->daily_sequence);
    }

    public function test_rejects_product_with_zero_stock(): void
    {
        $client = ClientFactory::new()->create();
        $product = ProductFactory::new()->outOfStock()->create();

        try {
            $this->action->execute(CreateSaleData::from([
                'client_id' => $client->id,
                'items' => [['product_id' => $product->id, 'service_id' => null, 'quantity' => 1]],
            ]));
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals(ApiErrorCode::InsufficientStock, $e->errorCode);
        }
    }

    public function test_rejects_product_when_quantity_exceeds_available_stock(): void
    {
        $client = ClientFactory::new()->create();
        $product = ProductFactory::new()->create(['stock' => 2]);

        try {
            $this->action->execute(CreateSaleData::from([
                'client_id' => $client->id,
                'items' => [['product_id' => $product->id, 'service_id' => null, 'quantity' => 5]],
            ]));
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals(ApiErrorCode::InsufficientStock, $e->errorCode);
        }
    }

    public function test_rejects_disabled_service(): void
    {
        $client = ClientFactory::new()->create();
        $service = ServiceFactory::new()->disabled()->create();

        try {
            $this->action->execute(CreateSaleData::from([
                'client_id' => $client->id,
                'items' => [['product_id' => null, 'service_id' => $service->id, 'quantity' => 1]],
            ]));
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals(ApiErrorCode::ServiceDependencyFailed, $e->errorCode);
        }
    }

    public function test_rejects_service_when_required_product_is_out_of_stock(): void
    {
        $client = ClientFactory::new()->create();
        $requiredProduct = ProductFactory::new()->outOfStock()->create();
        $service = ServiceFactory::new()->create(['required_product_id' => $requiredProduct->id]);

        try {
            $this->action->execute(CreateSaleData::from([
                'client_id' => $client->id,
                'items' => [['product_id' => null, 'service_id' => $service->id, 'quantity' => 1]],
            ]));
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals(ApiErrorCode::ServiceDependencyFailed, $e->errorCode);
        }
    }

    public function test_accepts_service_when_required_product_is_in_stock(): void
    {
        $client = ClientFactory::new()->create();
        $requiredProduct = ProductFactory::new()->create(['stock' => 5]);
        $service = ServiceFactory::new()->create(['required_product_id' => $requiredProduct->id, 'price' => 30.00]);

        $sale = $this->action->execute(CreateSaleData::from([
            'client_id' => $client->id,
            'items' => [['product_id' => null, 'service_id' => $service->id, 'quantity' => 1]],
        ]));

        $this->assertEquals(30.00, $sale->total);
    }

    public function test_three_client_limit_rejects_fourth_distinct_client(): void
    {
        $product = ProductFactory::new()->create(['stock' => 100]);

        for ($i = 0; $i < 3; $i++) {
            $existingClient = ClientFactory::new()->create();
            $sale = SaleFactory::new()->create(['client_id' => $existingClient->id]);
            SaleDetailFactory::new()->create(['sale_id' => $sale->id, 'product_id' => $product->id]);
        }

        $newClient = ClientFactory::new()->create();

        try {
            $this->action->execute(CreateSaleData::from([
                'client_id' => $newClient->id,
                'items' => [['product_id' => $product->id, 'service_id' => null, 'quantity' => 1]],
            ]));
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals(ApiErrorCode::DailyLimitReached, $e->errorCode);
        }
    }

    public function test_same_client_can_still_buy_when_three_other_clients_already_bought(): void
    {
        $product = ProductFactory::new()->create(['stock' => 100]);
        $existingClient = ClientFactory::new()->create();

        $firstSale = SaleFactory::new()->create(['client_id' => $existingClient->id]);
        SaleDetailFactory::new()->create(['sale_id' => $firstSale->id, 'product_id' => $product->id]);

        for ($i = 0; $i < 2; $i++) {
            $otherClient = ClientFactory::new()->create();
            $sale = SaleFactory::new()->create(['client_id' => $otherClient->id]);
            SaleDetailFactory::new()->create(['sale_id' => $sale->id, 'product_id' => $product->id]);
        }

        $sale = $this->action->execute(CreateSaleData::from([
            'client_id' => $existingClient->id,
            'items' => [['product_id' => $product->id, 'service_id' => null, 'quantity' => 1]],
        ]));

        $this->assertNotNull($sale->id);
    }

    public function test_multi_item_sale_with_product_and_service(): void
    {
        $client = ClientFactory::new()->create();
        $product = ProductFactory::new()->create(['stock' => 10, 'price' => 100.00]);
        $service = ServiceFactory::new()->create(['price' => 50.00]);

        $sale = $this->action->execute(CreateSaleData::from([
            'client_id' => $client->id,
            'items' => [
                ['product_id' => $product->id, 'service_id' => null, 'quantity' => 2],
                ['product_id' => null, 'service_id' => $service->id, 'quantity' => 1],
            ],
        ]));

        $this->assertEquals(250.00, $sale->total);
        $this->assertCount(2, $sale->details);
    }

    public function test_decrements_product_stock_after_sale(): void
    {
        $client = ClientFactory::new()->create();
        $product = ProductFactory::new()->create(['stock' => 10]);

        $this->action->execute(CreateSaleData::from([
            'client_id' => $client->id,
            'items' => [['product_id' => $product->id, 'service_id' => null, 'quantity' => 3]],
        ]));

        $this->assertEquals(7, $product->fresh()->stock);
    }
}
