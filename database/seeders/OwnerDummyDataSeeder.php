<?php

namespace Database\Seeders;

/**
 * Owner Dummy Data Seeder
 * 
 * This seeder creates comprehensive dummy data for an owner including:
 * - 1 Owner user (owner@example.com / password)
 * - 5 Locations
 * - 20 Apartments (mix of occupied, available, maintenance)
 * - 15 Tenants (mix of active and inactive with historical data)
 * - 150+ Rent Payments (spread across last 12+ months for graphs)
 * - Notifications (various types)
 * 
 * Usage:
 *   php artisan db:seed --class=OwnerDummyDataSeeder
 * 
 * Note: Running multiple times will create duplicate data.
 * For fresh data, refresh migrations first:
 *   php artisan migrate:fresh --seed
 */

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Location;
use App\Models\Apartment;
use App\Models\Tenant;
use App\Models\RentPayment;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class OwnerDummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get Owner user
        $owner = User::firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'John Property Owner',
                'password' => Hash::make('password'),
                'role' => 'owner',
            ]
        );

        $this->command->info("Created/Found Owner: {$owner->name} (ID: {$owner->id})");

        // Create Locations
        $locations = [
            ['name' => 'Downtown District', 'description' => 'Prime location in the city center'],
            ['name' => 'Riverside Complex', 'description' => 'Modern apartments near the river'],
            ['name' => 'Garden Estates', 'description' => 'Family-friendly neighborhood'],
            ['name' => 'Metro Heights', 'description' => 'High-rise apartments with city views'],
            ['name' => 'Parkview Residences', 'description' => 'Apartments overlooking the park'],
        ];

        $createdLocations = [];
        foreach ($locations as $locationData) {
            $location = Location::firstOrCreate(
                ['name' => $locationData['name']],
                $locationData
            );
            $createdLocations[] = $location;
            $this->command->info("Created/Found Location: {$location->name}");
        }

        // Create Apartments
        $apartmentNames = [
            'Sunset View', 'Ocean Breeze', 'Mountain Peak', 'City Lights', 'Garden Villa',
            'Skyline Suite', 'Riverside Loft', 'Park Avenue', 'Harbor View', 'Sunrise Apartment',
            'Green Valley', 'Crystal Tower', 'Emerald Heights', 'Diamond Plaza', 'Golden Gate',
            'Silver Springs', 'Blue Horizon', 'Redwood Manor', 'Maple Court', 'Oak Terrace',
        ];

        $apartmentStatuses = ['occupied', 'available', 'maintenance'];
        $apartments = [];

        for ($i = 0; $i < 20; $i++) {
            $location = $createdLocations[array_rand($createdLocations)];
            $status = $apartmentStatuses[array_rand($apartmentStatuses)];
            
            // Adjust status distribution: 60% occupied, 30% available, 10% maintenance
            if ($i < 12) {
                $status = 'occupied';
            } elseif ($i < 18) {
                $status = 'available';
            } else {
                $status = 'maintenance';
            }

            $bedrooms = rand(1, 4);
            $bathrooms = $bedrooms <= 1 ? 1 : ($bedrooms - 1);
            $monthlyRent = rand(15000, 50000); // PHP pesos
            $squareMeters = rand(30, 120);

            $apartment = Apartment::create([
                'owner_id' => $owner->id,
                'location_id' => $location->id,
                'name' => $apartmentNames[$i],
                'address' => "{$location->name}, Unit " . ($i + 1) . ", Metro Manila",
                'unit_number' => 'UNIT-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'monthly_rent' => $monthlyRent,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'square_meters' => $squareMeters,
                'status' => $status,
                'description' => "Beautiful {$bedrooms}-bedroom apartment in {$location->name}",
                'amenities' => json_encode(['Air Conditioning', 'WiFi', 'Parking', 'Security']),
            ]);

            $apartments[] = $apartment;
            $this->command->info("Created Apartment: {$apartment->name} ({$status})");
        }

        // Create Tenants
        $tenantNames = [
            'Maria Santos', 'Juan Dela Cruz', 'Anna Garcia', 'Carlos Rodriguez', 'Lisa Martinez',
            'Michael Tan', 'Sarah Lee', 'David Chen', 'Jennifer Wong', 'Robert Kim',
            'Emily Brown', 'James Wilson', 'Sophia Davis', 'Daniel Miller', 'Olivia Anderson',
        ];

        $tenants = [];
        $now = Carbon::now();

        for ($i = 0; $i < 15; $i++) {
            // Get an occupied apartment or available one
            $availableApartments = array_filter($apartments, fn($apt) => 
                $apt->status === 'occupied' || $apt->status === 'available'
            );
            $apartment = $availableApartments[array_rand($availableApartments)];

            // Create tenants with move-in dates spread over the past 2 years
            $monthsAgo = rand(0, 24);
            $moveInDate = $now->copy()->subMonths($monthsAgo)->subDays(rand(0, 28));
            
            // Determine if tenant is active or inactive
            $isActive = rand(1, 10) <= 7; // 70% active
            
            $leaseStartDate = $moveInDate->copy();
            $leaseEndDate = null;
            
            if ($isActive) {
                // Active tenants have future lease end dates or no end date
                if (rand(1, 10) <= 8) {
                    $leaseEndDate = $leaseStartDate->copy()->addMonths(rand(6, 24));
                }
            } else {
                // Inactive tenants have past lease end dates
                $leaseEndDate = $leaseStartDate->copy()->addMonths(rand(6, 18));
                // Make sure it's in the past
                if ($leaseEndDate->isFuture()) {
                    $leaseEndDate = $now->copy()->subMonths(rand(1, 6));
                }
            }

            $monthlyRent = $apartment->monthly_rent;
            $depositAmount = $monthlyRent * rand(2, 3);

            $tenant = Tenant::create([
                'apartment_id' => $apartment->id,
                'owner_id' => $owner->id,
                'name' => $tenantNames[$i],
                'email' => strtolower(str_replace(' ', '.', $tenantNames[$i])) . '@example.com',
                'phone' => '+63' . rand(9000000000, 9999999999),
                'emergency_contact' => 'Emergency Contact ' . ($i + 1),
                'emergency_phone' => '+63' . rand(9000000000, 9999999999),
                'move_in_date' => $moveInDate,
                'lease_start_date' => $leaseStartDate,
                'lease_end_date' => $leaseEndDate,
                'monthly_rent' => $monthlyRent,
                'deposit_amount' => $depositAmount,
                'status' => $isActive ? 'active' : 'inactive',
                'notes' => $isActive ? 'Current tenant' : 'Previous tenant',
            ]);

            // Update apartment status to occupied if tenant is active
            if ($isActive && $apartment->status === 'available') {
                $apartment->update(['status' => 'occupied']);
            }

            $tenants[] = $tenant;
            $this->command->info("Created Tenant: {$tenant->name} ({$tenant->status})");
        }

        // Create additional historical tenants for better revenue trend data
        $additionalTenantNames = [
            'Patricia Cruz', 'Mark Torres', 'Grace Fernandez', 'Ryan Lim', 'Michelle Reyes',
            'Kevin Ong', 'Nicole Tan', 'Andrew Sy', 'Christine Yu', 'Brian Chua',
        ];
        
        $historicalTenantsCreated = 0;
        for ($i = 0; $i < 8; $i++) {
            // Use available apartments or occupied ones
            $availableApartments = array_filter($apartments, fn($apt) => 
                $apt->status === 'available' || $apt->status === 'occupied'
            );
            if (empty($availableApartments)) {
                break;
            }
            
            $apartment = $availableApartments[array_rand($availableApartments)];
            
            // Create tenants that started 12-18 months ago (historical data)
            $monthsAgo = rand(12, 18);
            $moveInDate = $now->copy()->subMonths($monthsAgo)->subDays(rand(0, 28));
            $leaseStartDate = $moveInDate->copy();
            
            // These are mostly inactive (moved out) to create historical payment data
            $leaseEndDate = $leaseStartDate->copy()->addMonths(rand(6, 12));
            // Ensure lease ended in the past but not too long ago
            if ($leaseEndDate->isFuture() || $leaseEndDate->lt($now->copy()->subMonths(3))) {
                $leaseEndDate = $now->copy()->subMonths(rand(1, 3));
            }
            
            $monthlyRent = $apartment->monthly_rent;
            $depositAmount = $monthlyRent * rand(2, 3);
            
            $tenant = Tenant::create([
                'apartment_id' => $apartment->id,
                'owner_id' => $owner->id,
                'name' => $additionalTenantNames[$i],
                'email' => strtolower(str_replace(' ', '.', $additionalTenantNames[$i])) . '@example.com',
                'phone' => '+63' . rand(9000000000, 9999999999),
                'emergency_contact' => 'Emergency Contact ' . (15 + $i + 1),
                'emergency_phone' => '+63' . rand(9000000000, 9999999999),
                'move_in_date' => $moveInDate,
                'lease_start_date' => $leaseStartDate,
                'lease_end_date' => $leaseEndDate,
                'monthly_rent' => $monthlyRent,
                'deposit_amount' => $depositAmount,
                'status' => 'inactive', // Historical tenants
                'notes' => 'Previous tenant - historical data',
            ]);
            
            $tenants[] = $tenant;
            $historicalTenantsCreated++;
            $this->command->info("Created Historical Tenant: {$tenant->name} (inactive)");
        }
        
        if ($historicalTenantsCreated > 0) {
            $this->command->info("Created {$historicalTenantsCreated} additional historical tenants for revenue trend");
        }

        // Create additional tenants for 2026 turnover data
        $turnover2026Names = [
            'Alex Rivera', 'Bianca Lopez', 'Christian Morales', 'Diana Ramos', 'Eduardo Santos',
            'Fatima Cruz', 'Gabriel Torres', 'Hannah Reyes', 'Ivan Mendoza', 'Julia Fernandez',
            'Kyle Villanueva', 'Luna Dela Cruz', 'Marcus Tan', 'Nina Garcia', 'Oscar Lim',
        ];
        
        $turnover2026Created = 0;
        $currentYear = $now->year;
        
        // Create tenants who moved in during 2026
        for ($i = 0; $i < 10; $i++) {
            $availableApartments = array_filter($apartments, fn($apt) => 
                $apt->status === 'available' || $apt->status === 'occupied'
            );
            if (empty($availableApartments)) {
                break;
            }
            
            $apartment = $availableApartments[array_rand($availableApartments)];
            
            // Move-in dates spread across 2026 (distribute across all 12 months for better visualization)
            // Use modulo to ensure distribution across months
            $month = ($i % 12) + 1; // Cycle through months 1-12
            $day = rand(1, 28);
            $moveInDate = Carbon::create(2026, $month, $day);
            $leaseStartDate = $moveInDate->copy();
            
            // Some will be active (moved in recently), some inactive (short-term leases that ended)
            $isActive = rand(1, 10) <= 6; // 60% active
            
            if ($isActive) {
                // Active tenants - lease ends in future
                $leaseEndDate = $leaseStartDate->copy()->addMonths(rand(6, 18));
            } else {
                // Inactive tenants - moved in and out in 2026 (short-term leases)
                $leaseEndDate = $leaseStartDate->copy()->addMonths(rand(3, 6));
                // Ensure it's still in 2026 or early 2027
                if ($leaseEndDate->year > 2026) {
                    $leaseEndDate = Carbon::create(2026, 12, 31);
                }
            }
            
            $monthlyRent = $apartment->monthly_rent;
            $depositAmount = $monthlyRent * rand(2, 3);
            
            $tenant = Tenant::create([
                'apartment_id' => $apartment->id,
                'owner_id' => $owner->id,
                'name' => $turnover2026Names[$i],
                'email' => strtolower(str_replace(' ', '.', $turnover2026Names[$i])) . '@example.com',
                'phone' => '+63' . rand(9000000000, 9999999999),
                'emergency_contact' => 'Emergency Contact ' . (23 + $i + 1),
                'emergency_phone' => '+63' . rand(9000000000, 9999999999),
                'move_in_date' => $moveInDate,
                'lease_start_date' => $leaseStartDate,
                'lease_end_date' => $leaseEndDate,
                'monthly_rent' => $monthlyRent,
                'deposit_amount' => $depositAmount,
                'status' => $isActive ? 'active' : 'inactive',
                'notes' => '2026 tenant - turnover data',
            ]);
            
            if ($isActive && $apartment->status === 'available') {
                $apartment->update(['status' => 'occupied']);
            }
            
            $tenants[] = $tenant;
            $turnover2026Created++;
            $this->command->info("Created 2026 Tenant: {$tenant->name} ({$tenant->status}) - Moved in: {$moveInDate->format('M Y')}");
        }
        
        // Create tenants who moved out in 2026 (started in 2025, ended in 2026)
        for ($i = 10; $i < 15; $i++) {
            $availableApartments = array_filter($apartments, fn($apt) => 
                $apt->status === 'available' || $apt->status === 'occupied'
            );
            if (empty($availableApartments)) {
                break;
            }
            
            $apartment = $availableApartments[array_rand($availableApartments)];
            
            // Started in late 2025, moved out in 2026
            $startMonth = rand(7, 12); // July to December 2025
            $startDay = rand(1, 28);
            $moveInDate = Carbon::create(2025, $startMonth, $startDay);
            $leaseStartDate = $moveInDate->copy();
            
            // Moved out in 2026 - distribute across different months
            // Use the index to cycle through months for better distribution
            $endMonth = (($i - 10) % 12) + 1; // Cycle through months 1-12
            $endDay = rand(1, 28);
            $leaseEndDate = Carbon::create(2026, $endMonth, $endDay);
            
            // Make sure lease end is after lease start
            if ($leaseEndDate->lte($leaseStartDate)) {
                $leaseEndDate = $leaseStartDate->copy()->addMonths(rand(3, 6));
            }
            
            $monthlyRent = $apartment->monthly_rent;
            $depositAmount = $monthlyRent * rand(2, 3);
            
            $tenant = Tenant::create([
                'apartment_id' => $apartment->id,
                'owner_id' => $owner->id,
                'name' => $turnover2026Names[$i],
                'email' => strtolower(str_replace(' ', '.', $turnover2026Names[$i])) . '@example.com',
                'phone' => '+63' . rand(9000000000, 9999999999),
                'emergency_contact' => 'Emergency Contact ' . (23 + $i + 1),
                'emergency_phone' => '+63' . rand(9000000000, 9999999999),
                'move_in_date' => $moveInDate,
                'lease_start_date' => $leaseStartDate,
                'lease_end_date' => $leaseEndDate,
                'monthly_rent' => $monthlyRent,
                'deposit_amount' => $depositAmount,
                'status' => 'inactive', // Moved out
                'notes' => 'Moved out in 2026 - turnover data',
            ]);
            
            $tenants[] = $tenant;
            $turnover2026Created++;
            $this->command->info("Created 2026 Move-out Tenant: {$tenant->name} (inactive) - Moved out: {$leaseEndDate->format('M Y')}");
        }
        
        if ($turnover2026Created > 0) {
            $this->command->info("Created {$turnover2026Created} additional tenants for 2026 turnover data");
        }

        // Create Rent Payments for the last 12+ months
        // CRITICAL: Ensure payments are created for the last 12 months from TODAY
        $paymentMethods = ['Bank Transfer', 'Cash', 'Credit Card', 'GCash', 'PayMaya'];
        $paymentCount = 0;
        $twelveMonthsAgo = $now->copy()->subMonths(11)->startOfMonth(); // Start from 12 months ago

        foreach ($tenants as $tenant) {
            $leaseStart = $tenant->lease_start_date ?? $tenant->move_in_date;
            
            // For active tenants, create payments until now
            // For inactive tenants, create payments until lease end (historical data)
            if ($tenant->status === 'active') {
                $leaseEnd = $tenant->lease_end_date ?? $now->copy()->addMonths(12);
                $endDate = $leaseEnd->isPast() ? $leaseEnd : $now;
            } else {
                // Inactive tenants - create historical payments until lease end
                $endDate = $tenant->lease_end_date ?? $leaseStart->copy()->addMonths(12);
            }
            
            // Start from 12 months ago OR lease start, whichever is later
            // But ensure we cover the last 12 months
            $startDate = max($leaseStart->copy()->startOfMonth(), $twelveMonthsAgo);
            $currentDate = $startDate->copy();
            
            // Don't go beyond now
            $actualEndDate = min($endDate, $now);

            while ($currentDate->lte($actualEndDate)) {
                $dueDate = $currentDate->copy()->day(5); // Due on 5th of each month
                
                // Skip if this month is too far in the future
                if ($dueDate->gt($now)) {
                    $currentDate->addMonth();
                    continue;
                }
                
                // Determine payment status
                $statusRand = rand(1, 100);
                $status = 'paid';
                $paymentDate = null;

                if ($tenant->status === 'active') {
                    // Active tenants: 70% paid, 15% pending, 15% overdue
                    if ($statusRand <= 70) {
                        $status = 'paid';
                        $paymentDate = $dueDate->copy()->addDays(rand(0, 5)); // Paid within 5 days of due date
                        // Ensure payment date is not in the future
                        if ($paymentDate->gt($now)) {
                            $paymentDate = $now->copy()->subDays(rand(0, 3));
                        }
                    } elseif ($statusRand <= 85) {
                        // 15% pending (due date in future)
                        $status = 'pending';
                        if ($dueDate->isPast()) {
                            $dueDate = $now->copy()->addDays(rand(1, 15)); // Due in next 15 days
                        }
                    } else {
                        // 15% overdue
                        $status = 'overdue';
                        if ($dueDate->isFuture()) {
                            $dueDate = $now->copy()->subDays(rand(1, 30)); // Overdue by 1-30 days
                        }
                    }
                } else {
                    // Inactive tenants: mostly paid (historical data)
                    if ($statusRand <= 90) {
                        $status = 'paid';
                        $paymentDate = $dueDate->copy()->addDays(rand(0, 10)); // Paid within 10 days
                        // Ensure payment date is not in the future
                        if ($paymentDate->gt($now)) {
                            $paymentDate = $now->copy()->subDays(rand(0, 3));
                        }
                    } else {
                        // Some overdue for historical context
                        $status = 'overdue';
                    }
                }

                // Only create payments for the last 12 months
                if ($currentDate->lt($twelveMonthsAgo)) {
                    $currentDate->addMonth();
                    continue;
                }

                $payment = RentPayment::create([
                    'tenant_id' => $tenant->id,
                    'apartment_id' => $tenant->apartment_id,
                    'amount' => $tenant->monthly_rent,
                    'payment_date' => $paymentDate,
                    'due_date' => $dueDate,
                    'status' => $status,
                    'payment_method' => $status === 'paid' ? $paymentMethods[array_rand($paymentMethods)] : null,
                    'reference_number' => $status === 'paid' ? 'REF-' . strtoupper(uniqid()) : null,
                ]);

                $paymentCount++;
                $currentDate->addMonth();
            }
        }

        $this->command->info("Created {$paymentCount} rent payments");

        // Ensure Monthly Revenue Trend has data for all 12 months
        // Check which months have paid payments and fill any gaps
        $twelveMonthsAgo = $now->copy()->subMonths(11)->startOfMonth();
        $monthlyRevenueData = [];
        
        for ($i = 0; $i < 12; $i++) {
            $month = $twelveMonthsAgo->copy()->addMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            // Count paid payments for this month
            $paidCount = RentPayment::whereHas('tenant', function($q) use ($owner) {
                    $q->where('owner_id', $owner->id);
                })
                ->where('status', 'paid')
                ->whereNotNull('payment_date')
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->count();
            
            $monthlyRevenueData[$month->format('Y-m')] = $paidCount;
        }
        
        // Fill gaps and add variation to show trends
        $additionalPayments = 0;
        // Use all tenants for gap filling (both active and inactive for historical data)
        $allTenantsForPayments = Tenant::where('owner_id', $owner->id)->get();
        
        if ($allTenantsForPayments->isEmpty()) {
            // Fallback to collected tenants
            $allTenantsForPayments = collect($tenants);
        }
        
        // Create a realistic revenue trend (gradual growth pattern)
        $trendMultiplier = [0.8, 0.85, 0.9, 0.92, 0.95, 0.98, 1.0, 1.02, 1.05, 1.08, 1.1, 1.12]; // Growth trend
        
        $monthKeys = array_keys($monthlyRevenueData);
        $index = 0;
        foreach ($monthlyRevenueData as $monthKey => $paidCount) {
            $month = Carbon::createFromFormat('Y-m', $monthKey);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            // Skip future months
            if ($monthStart->gt($now)) {
                $index++;
                continue;
            }
            
            // Target: 8-15 paid payments per month for good chart visibility
            $targetPayments = (int)(12 * $trendMultiplier[$index]);
            
            if ($paidCount < $targetPayments) {
                $needed = $targetPayments - $paidCount;
                
                // Create additional paid payments for this month
                $tenantIndex = 0;
                $createdThisMonth = 0;
                
                while ($createdThisMonth < $needed && $tenantIndex < count($allTenantsForPayments)) {
                    $tenant = $allTenantsForPayments[$tenantIndex % count($allTenantsForPayments)];
                    $tenantIndex++;
                    
                    // Check if this tenant already has a payment for this month
                    $existingPayment = RentPayment::where('tenant_id', $tenant->id)
                        ->where('status', 'paid')
                        ->whereNotNull('payment_date')
                        ->whereBetween('payment_date', [$monthStart, $monthEnd])
                        ->exists();
                    
                    if (!$existingPayment) {
                        $dueDate = $month->copy()->day(5);
                        $paymentDate = $dueDate->copy()->addDays(rand(0, 5));
                        
                        // Ensure payment date is within the month and not in the future
                        if ($paymentDate->gt($monthEnd)) {
                            $paymentDate = $monthEnd->copy()->subDays(rand(0, 3));
                        }
                        if ($paymentDate->gt($now)) {
                            $paymentDate = $now->copy()->subDays(rand(0, 3));
                        }
                        
                        RentPayment::create([
                            'tenant_id' => $tenant->id,
                            'apartment_id' => $tenant->apartment_id,
                            'amount' => $tenant->monthly_rent,
                            'payment_date' => $paymentDate,
                            'due_date' => $dueDate,
                            'status' => 'paid',
                            'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                            'reference_number' => 'REF-' . strtoupper(uniqid()),
                        ]);
                        
                        $additionalPayments++;
                        $createdThisMonth++;
                    }
                }
            }
            $index++;
        }
        
        if ($additionalPayments > 0) {
            $this->command->info("Created {$additionalPayments} additional payments to ensure monthly revenue trend data");
            $paymentCount += $additionalPayments;
        }

        // Create Notifications
        $notificationTypes = [
            'payment_received' => 'Payment Received',
            'payment_overdue' => 'Payment Overdue',
            'lease_expiring' => 'Lease Expiring Soon',
            'new_tenant' => 'New Tenant',
            'maintenance_request' => 'Maintenance Request',
            'task_assigned' => 'Task Assigned',
        ];

        $notifications = [];
        $notificationCount = 0;

        // Create notifications for overdue payments
        $overduePayments = RentPayment::whereHas('tenant', function($q) use ($owner) {
            $q->where('owner_id', $owner->id);
        })->where('status', 'overdue')->limit(5)->get();

        foreach ($overduePayments as $payment) {
            $notification = Notification::create([
                'user_id' => $owner->id,
                'type' => 'payment_overdue',
                'title' => 'Payment Overdue',
                'message' => "Payment of ₱" . number_format($payment->amount, 2) . " from {$payment->tenant->name} is overdue.",
                'read_at' => rand(1, 10) <= 3 ? now() : null, // 30% read
            ]);
            $notificationCount++;
        }

        // Create notifications for leases expiring soon
        $expiringTenants = Tenant::where('owner_id', $owner->id)
            ->where('status', 'active')
            ->whereNotNull('lease_end_date')
            ->whereBetween('lease_end_date', [$now, $now->copy()->addDays(30)])
            ->limit(3)
            ->get();

        foreach ($expiringTenants as $tenant) {
            $notification = Notification::create([
                'user_id' => $owner->id,
                'type' => 'lease_expiring',
                'title' => 'Lease Expiring Soon',
                'message' => "Lease for {$tenant->name} at {$tenant->apartment->name} expires on " . $tenant->lease_end_date->format('M d, Y') . ".",
                'read_at' => rand(1, 10) <= 4 ? now() : null, // 40% read
            ]);
            $notificationCount++;
        }

        // Create some payment received notifications
        $recentPayments = RentPayment::whereHas('tenant', function($q) use ($owner) {
            $q->where('owner_id', $owner->id);
        })
        ->where('status', 'paid')
        ->where('payment_date', '>=', $now->copy()->subDays(7))
        ->limit(5)
        ->get();

        foreach ($recentPayments as $payment) {
            $notification = Notification::create([
                'user_id' => $owner->id,
                'type' => 'payment_received',
                'title' => 'Payment Received',
                'message' => "Received ₱" . number_format($payment->amount, 2) . " from {$payment->tenant->name}.",
                'read_at' => rand(1, 10) <= 5 ? now() : null, // 50% read
            ]);
            $notificationCount++;
        }

        // Create some general notifications
        for ($i = 0; $i < 5; $i++) {
            $type = array_rand($notificationTypes);
            $notification = Notification::create([
                'user_id' => $owner->id,
                'type' => $type,
                'title' => $notificationTypes[$type],
                'message' => "This is a sample notification for {$notificationTypes[$type]}.",
                'read_at' => rand(1, 10) <= 6 ? now() : null, // 60% read
            ]);
            $notificationCount++;
        }

        $this->command->info("Created {$notificationCount} notifications");

        $this->command->info("\n=== Summary ===");
        $this->command->info("Owner: {$owner->name} (ID: {$owner->id})");
        $this->command->info("Locations: " . count($createdLocations));
        $this->command->info("Apartments: " . count($apartments));
        $this->command->info("Tenants: " . count($tenants));
        $this->command->info("Rent Payments: {$paymentCount}");
        $this->command->info("Notifications: {$notificationCount}");
        $this->command->info("\nYou can now login with:");
        $this->command->info("Email: owner@example.com");
        $this->command->info("Password: password");
    }
}
