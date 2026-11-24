<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Who did what. Append-only; the app never edits or deletes rows.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40)->index();          // e.g. login, order.cancel, expense.delete
            $table->string('subject_type', 40)->nullable(); // order | expense | product | user | setting
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 500);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
