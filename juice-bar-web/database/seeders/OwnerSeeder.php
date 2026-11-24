<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * First owner account. Set SEED_OWNER_EMAIL / SEED_OWNER_PASSWORD in .env,
 * otherwise a random password is generated and printed once.
 */
class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('role', 'owner')->exists()) {
            return;
        }

        $email = env('SEED_OWNER_EMAIL', 'owner@roshbar.com');
        $password = env('SEED_OWNER_PASSWORD') ?: Str::password(14, symbols: false);

        User::query()->create(['name' => 'Owner', 'email' => $email, 'password' => $password, 'role' => 'owner', 'is_active' => true]);

        if (! env('SEED_OWNER_PASSWORD')) {
            $this->command?->warn("Owner login: {$email} / {$password}  (change it after first sign-in)");
        }
    }
}
