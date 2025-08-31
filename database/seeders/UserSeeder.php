<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create employer users
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => 'Employer ' . $i,
                'email' => 'employer' . $i . '@example.com',
                'password' => Hash::make('password'),
                'role' => 'employer',
                'company_name' => 'Company ' . $i . ' Ltd.',
                'phone' => '+8801' . rand(5, 9) . rand(1000000, 9999999),
                'address' => $i . ' Business Street, Dhaka ' . (1000 + $i),
                'bio' => 'We are a leading company in our industry with ' . ($i * 10) . ' years of experience.',
                'email_verified_at' => now(),
            ]);
        }

        // Create job seeker users
        for ($i = 1; $i <= 20; $i++) {
            User::create([
                'name' => 'Job Seeker ' . $i,
                'email' => 'jobseeker' . $i . '@example.com',
                'password' => Hash::make('password'),
                'role' => 'job_seeker',
                'phone' => '+8801' . rand(5, 9) . rand(1000000, 9999999),
                'address' => ($i % 5 + 1) . ' Residential Area, Dhaka ' . (1200 + $i),
                'bio' => 'Experienced professional with expertise in multiple domains. Looking for new opportunities.',
                'email_verified_at' => now(),
            ]);
        }
    }
}
