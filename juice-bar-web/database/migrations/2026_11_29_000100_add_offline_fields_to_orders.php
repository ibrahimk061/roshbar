<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Taken while the POS had no internet and sent later. created_at is then the
            // time the cashier took the order, synced_at when the server received it.
            $table->boolean('was_offline')->default(false)->after('note');
            $table->timestamp('synced_at')->nullable()->after('was_offline');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['was_offline', 'synced_at']));
    }
};
