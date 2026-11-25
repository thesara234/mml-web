<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('local_currency', 3);
            $table->string('foreign_currency', 3);
            $table->decimal('rate', 12, 6)->comment('1 local = rate × foreign');
            $table->date('effective_date');
            $table->foreignId('set_by_staff_id')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['local_currency', 'foreign_currency', 'effective_date'], 'rates_pair_date_unique');
        });

        // Tiered service fees per corridor; amounts are in the local (sending) currency.
        Schema::create('fee_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('local_currency', 3);
            $table->string('foreign_currency', 3);
            $table->decimal('min_amount', 12, 2)->default(0);
            $table->decimal('max_amount', 12, 2)->nullable()->comment('NULL = no upper limit');
            $table->enum('fee_type', ['flat', 'percent'])->default('flat');
            $table->decimal('fee_value', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->comment('Store that holds and hands out this item');
            $table->string('sku', 40)->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('unit')->default('each');
            $table->decimal('price', 12, 2);
            $table->string('currency', 3);
            $table->integer('quantity_on_hand')->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
        Schema::dropIfExists('fee_tiers');
        Schema::dropIfExists('exchange_rates');
    }
};
