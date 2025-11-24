<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\CreditTransaction;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'credits' => 10,
            'role' => 'user',
            'is_active' => true,
        ]);

        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 10,
            'type' => 'initial',
            'description' => 'Initial free credits',
        ]);
    }
}
