<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();          // cash | bkash | nagad | card | bank | ...
            $table->string('name', 60);
            $table->boolean('is_cash')->default(false);     // gives change, counts in the cash drawer
            $table->boolean('requires_reference')->default(false); // e.g. bKash TrxID
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('payment_methods')->insert([
            ['code' => 'cash', 'name' => 'Cash', 'is_cash' => true, 'requires_reference' => false, 'sort_order' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'bkash', 'name' => 'bKash', 'is_cash' => false, 'requires_reference' => true, 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'nagad', 'name' => 'Nagad', 'is_cash' => false, 'requires_reference' => true, 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'card', 'name' => 'Card', 'is_cash' => false, 'requires_reference' => false, 'sort_order' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'bank', 'name' => 'Bank', 'is_cash' => false, 'requires_reference' => false, 'sort_order' => 4, 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_type', 10)->nullable()->after('subtotal');   // amount | percent
            $table->decimal('discount_value', 12, 2)->nullable()->after('discount_type');
            $table->string('discount_reason', 255)->nullable()->after('discount_total');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('discount_reason');
            $table->boolean('tax_inclusive')->default(false)->after('tax_rate');
            $table->decimal('paid_total', 12, 2)->default(0)->after('grand_total');
            $table->decimal('change_total', 12, 2)->default(0)->after('paid_total');
            $table->string('payment_status', 20)->default('unpaid')->after('change_total'); // unpaid | partial | paid
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason', 255)->nullable();
            $table->index(['status', 'business_date']);
        });

        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->string('method_name', 60);                  // snapshot
            $table->decimal('amount', 12, 2);                   // applied to the bill
            $table->decimal('tendered', 12, 2)->nullable();     // cash handed over
            $table->decimal('change', 12, 2)->default(0);       // cash given back
            $table->string('reference', 100)->nullable();       // TrxID / card slip no.
            $table->string('status', 20)->default('completed'); // completed (pending/refunded later)
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_payments');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'business_date']);
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['discount_type', 'discount_value', 'discount_reason', 'tax_rate', 'tax_inclusive',
                'paid_total', 'change_total', 'payment_status', 'completed_at', 'cancelled_at', 'cancel_reason']);
        });
        Schema::dropIfExists('payment_methods');
    }
};
