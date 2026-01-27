<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call(CountrySeeder::class);
        // $this->call(LanguageSeeder::class);
        // User::factory(50)->create();
        $this->call(OwnerDummyDataSeeder::class);

        
        // Uncomment the line below to seed notification demo data
        // $this->call(NotificationDemoSeeder::class);
        
        // Uncomment the line below to seed owner dummy data (apartments, tenants, payments, notifications)
        // $this->call(OwnerDummyDataSeeder::class);
    }
}
