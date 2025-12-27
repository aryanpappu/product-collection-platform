<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class MerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        User::create([
            'name' => 'Acme Electronics',
            'email' => 'merchant1@acme-electronics.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Best Fashion Store',
            'email' => 'merchant2@bestfashion.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Global Gadgets Inc',
            'email' => 'merchant3@globalgadgets.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }
}
