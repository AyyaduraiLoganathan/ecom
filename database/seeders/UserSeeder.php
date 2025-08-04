<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@ecommerce.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '+1234567890',
            'date_of_birth' => '1990-01-01',
            'gender' => 'other',
            'address' => json_encode([
                'street' => '123 Admin Street',
                'city' => 'Admin City',
                'state' => 'AC',
                'postal_code' => '12345',
                'country' => 'United States'
            ]),
            'is_admin' => true,
        ]);

        // Create test user
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '+1987654321',
            'date_of_birth' => '1985-06-15',
            'gender' => 'male',
            'address' => json_encode([
                'street' => '456 Test Avenue',
                'city' => 'Test City',
                'state' => 'TC',
                'postal_code' => '54321',
                'country' => 'United States'
            ]),
            'is_admin' => false,
        ]);

        // Create additional random users
        for ($i = 0; $i < 10; $i++) {
            User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'email_verified_at' => $faker->boolean(80) ? now() : null, // 80% verified
                'password' => Hash::make('password'),
                'phone' => $faker->phoneNumber,
                'date_of_birth' => $faker->date('Y-m-d', '2000-01-01'),
                'gender' => $faker->randomElement(['male', 'female', 'other']),
                'address' => json_encode([
                    'street' => $faker->streetAddress,
                    'city' => $faker->city,
                    'state' => $faker->stateAbbr,
                    'postal_code' => $faker->postcode,
                    'country' => 'United States'
                ]),
                'is_admin' => false,
            ]);
        }
    }
}
