<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per business date; locked FOR UPDATE while issuing a number.
        Schema::create('order_sequences', function (Blueprint $table) {
            $table->id();
            $table->date('business_date')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();                  // client-generated, idempotency key
            $table->string('order_number', 40)->unique();    // RB-DD-MM-YY-A0001
            $table->date('business_date')->index();
            $table->unsignedInteger('sequence');
            $table->string('order_type', 20);                // takeaway | dine_in
            $table->string('status', 20)->default('new');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            // Snapshots: history must not change when the menu changes.
            $table->string('product_name');
            $table->string('variant_name', 60);
            $table->decimal('unit_price', 12, 2);            // variant price at sale time
            $table->decimal('addons_unit_total', 12, 2)->default(0); // sum of add-ons per 1 qty
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);            // (unit_price + addons_unit_total) * qty
            $table->string('sugar', 10)->nullable();         // 0 | 25 | 50 | 75 | 100
            $table->string('ice', 20)->nullable();           // no_ice | less_ice | regular
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('order_item_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('addon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('addon_name');
            $table->decimal('price', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_addons');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_sequences');
    }
};
