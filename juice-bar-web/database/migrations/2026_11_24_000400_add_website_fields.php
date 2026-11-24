<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields used by the public roshbar.com website. One product table serves
 * both the POS and the website, so a price change shows up everywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_bn')->nullable()->after('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('slug', 120)->nullable()->unique()->after('name');
            $table->string('name_bn', 120)->nullable()->after('slug');
            $table->text('description_bn')->nullable()->after('description');
            $table->string('ingredients', 255)->nullable()->after('description_bn');
            $table->string('ingredients_bn', 255)->nullable()->after('ingredients');
            $table->string('color', 7)->default('#FFB000')->after('ingredients_bn');   // card + glass colour
            $table->string('garnish', 20)->default('orange')->after('color');          // glass illustration
            $table->unsignedInteger('calories')->default(0)->after('garnish');
            $table->boolean('is_seasonal')->default(false)->after('calories');
            $table->string('season_months', 40)->nullable()->after('is_seasonal');    // "5,6,7"
            $table->boolean('is_featured')->default(false)->after('season_months');   // home page
            $table->boolean('show_on_website')->default(true)->after('is_featured');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('cashier')->after('password');      // owner | manager | cashier
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 20);
            $table->string('topic', 30)->default('general');                         // general | catering | feedback
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'is_active']));
        Schema::table('products', function (Blueprint $t) {
            $t->dropUnique(['slug']);
            $t->dropColumn(['slug', 'name_bn', 'description_bn', 'ingredients', 'ingredients_bn', 'color', 'garnish',
                'calories', 'is_seasonal', 'season_months', 'is_featured', 'show_on_website']);
        });
        Schema::table('categories', fn (Blueprint $t) => $t->dropColumn('name_bn'));
    }
};
