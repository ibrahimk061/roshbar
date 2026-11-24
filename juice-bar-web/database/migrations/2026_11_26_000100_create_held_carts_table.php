<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A cart parked at the counter ("hold"). No order number is used until it is placed.
        Schema::create('held_carts', function (Blueprint $table) {
            $table->id();
            $table->string('label', 60);
            $table->string('order_type', 20)->default('takeaway');
            $table->json('lines');
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('total', 12, 2)->default(0);    // display only; prices are re-checked on checkout
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('held_carts');
    }
};
