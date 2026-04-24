<?php

namespace Database\Seeders;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userExists = User::query()->where('email', 'user@example.com')->exists();

        if (! $userExists) {
            User::factory()->create([
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
            ]);
        }
    }
}
