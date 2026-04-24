<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sale_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('service_id')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE sale_details ADD CONSTRAINT chk_sale_details_product_or_service CHECK (product_id IS NOT NULL OR service_id IS NOT NULL)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_details');
    }
};
