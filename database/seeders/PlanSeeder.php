<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'short_description' => 'Get started with basic property management',
                'price' => 0,
                'annual_price' => null,
                'apartment_limit' => 3,
                'tenant_limit' => 3,
                'features' => [
                    'Manage up to 3 apartments',
                    'Manage up to 3 tenants',
                    'Rent payment tracking',
                    'Basic reports',
                    'In-app notifications',
                ],
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'short_description' => 'Perfect for small-scale landlords',
                'stripe_price_id' => null, // Set after creating Stripe Price
                'stripe_annual_price_id' => null,
                'price' => 19.00,
                'annual_price' => 190.00,
                'apartment_limit' => 5,
                'tenant_limit' => 10,
                'features' => [
                    'Manage up to 5 apartments',
                    'Manage up to 10 tenants',
                    'Rent payment tracking',
                    'Basic reports',
                    'In-app notifications',
                    'Task management',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'short_description' => 'For growing property portfolios',
                'stripe_price_id' => null,
                'stripe_annual_price_id' => null,
                'price' => 49.00,
                'annual_price' => 490.00,
                'apartment_limit' => 25,
                'tenant_limit' => 50,
                'features' => [
                    'Manage up to 25 apartments',
                    'Manage up to 50 tenants',
                    'Rent payment tracking',
                    'Advanced reports & exports',
                    'In-app notifications',
                    'Task management',
                    'S3 image storage',
                    'Priority support',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'short_description' => 'Enterprise-grade property management',
                'stripe_price_id' => null,
                'stripe_annual_price_id' => null,
                'price' => 99.00,
                'annual_price' => 990.00,
                'apartment_limit' => 0, // 0 = unlimited
                'tenant_limit' => 0,    // 0 = unlimited
                'features' => [
                    'Unlimited apartments',
                    'Unlimited tenants',
                    'Rent payment tracking',
                    'Advanced reports & exports',
                    'In-app notifications',
                    'Task management',
                    'S3 image storage',
                    'Multiple locations',
                    'Dedicated support',
                    'API access',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
