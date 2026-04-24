<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $dataset = $this->loadDataset();

        $this->truncateTables();

        $this->seedData($dataset);
    }

    private function seedData(array $dataset): void
    {
        DB::table('clients')->insert($dataset['clients']);
        DB::table('products')->insert($dataset['products']);
        DB::table('services')->insert($dataset['services']);

        foreach ($dataset['sales'] as $sale) {
            $saleId = DB::table('sales')->insertGetId([
                'client_id'      => $sale['client_id'],
                'daily_sequence' => $sale['daily_sequence'],
                'total'          => $sale['total'],
                'created_at'     => $sale['created_at'],
            ]);

            DB::table('sale_details')->insert(
                array_map(
                    fn (array $detail) => [
                        'sale_id'    => $saleId,
                        'product_id' => $detail['product_id'],
                        'service_id' => $detail['service_id'],
                        'quantity'   => $detail['quantity'],
                        'unit_price' => $detail['unit_price'],
                    ],
                    $sale['details']
                )
            );
        }
    }

    private function loadDataset(): array
    {
        return json_decode(
            file_get_contents(database_path('data/sales_dataset.json')),
            true
        );
    }

    private function truncateTables(): void
    {
        DB::table('sale_details')->delete();
        DB::table('sales')->delete();
        DB::table('clients')->delete();
        DB::table('products')->delete();
        DB::table('services')->delete();
    }
}
