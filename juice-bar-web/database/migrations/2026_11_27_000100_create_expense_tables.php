<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $names = ['Fruit & bazar', 'Ice', 'Milk', 'Cups & packaging', 'Rent', 'Electricity', 'Salary', 'Transport', 'Other'];
        DB::table('expense_categories')->insert(array_map(fn ($n, $i) => ['name' => $n, 'sort_order' => $i, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now], $names, array_keys($names)));

        // Shop spending. Shop purchases (fruit, ice, cups) are expenses too: there is no stock module.
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date')->index();               // business date (Asia/Dhaka)
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete(); // paid by cash / bKash / ...
            $table->string('note', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
