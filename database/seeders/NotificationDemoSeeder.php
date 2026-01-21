<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Location;
use App\Models\Apartment;
use App\Models\Tenant;
use App\Models\RentPayment;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class NotificationDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder creates dummy data to demonstrate the notification system:
     * - Creates an owner user
     * - Creates apartments and tenants
     * - Creates overdue rent payments (will trigger overdue_payment notifications)
     * - Creates tenants with leases expiring in 30, 60, and 90 days (will trigger lease_expiration notifications)
     * - Creates sample notifications to show in the UI
     */
    public function run(): void
    {
        // Step 1: Create an owner user
        $owner = User::firstOrCreate(
            ['email' => 'owner@demo.com'],
            [
                'name' => 'John Property Owner',
                'email' => 'owner@demo.com',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("✓ Created owner user: {$owner->name} ({$owner->email})");

        // Step 2: Create a location
        $location = Location::firstOrCreate(
            ['name' => 'Manila City'],
            [
                'name' => 'Manila City',
                'description' => 'Metro Manila, Philippines',
            ]
        );

        $this->command->info("✓ Created location: {$location->name}");

        // Step 3: Create apartments
        $apartments = [
            [
                'name' => 'Sunset Apartments',
                'unit_number' => '101',
                'monthly_rent' => 15000.00,
                'status' => 'occupied',
            ],
            [
                'name' => 'Sunset Apartments',
                'unit_number' => '202',
                'monthly_rent' => 18000.00,
                'status' => 'occupied',
            ],
            [
                'name' => 'Ocean View Residences',
                'unit_number' => '301',
                'monthly_rent' => 20000.00,
                'status' => 'occupied',
            ],
            [
                'name' => 'Ocean View Residences',
                'unit_number' => '402',
                'monthly_rent' => 22000.00,
                'status' => 'occupied',
            ],
        ];

        $createdApartments = [];
        foreach ($apartments as $aptData) {
            $apartment = Apartment::firstOrCreate(
                [
                    'owner_id' => $owner->id,
                    'location_id' => $location->id,
                    'unit_number' => $aptData['unit_number'],
                ],
                array_merge($aptData, [
                    'owner_id' => $owner->id,
                    'location_id' => $location->id,
                    'address' => "123 Main Street, {$location->name}",
                    'bedrooms' => 2,
                    'bathrooms' => 1,
                    'square_meters' => 50.00,
                    'description' => 'A cozy apartment unit',
                ])
            );
            $createdApartments[] = $apartment;
            $this->command->info("✓ Created apartment: {$apartment->name} - Unit {$apartment->unit_number}");
        }

        // Step 4: Create tenants with different lease expiration dates
        $today = Carbon::today();
        
        $tenantsData = [
            [
                'name' => 'Maria Santos',
                'email' => 'maria@example.com',
                'phone' => '+63 912 345 6789',
                'apartment' => $createdApartments[0],
                'lease_start_date' => $today->copy()->subMonths(10),
                'lease_end_date' => $today->copy()->addDays(30), // Expires in 30 days
                'monthly_rent' => 15000.00,
            ],
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'juan@example.com',
                'phone' => '+63 912 345 6790',
                'apartment' => $createdApartments[1],
                'lease_start_date' => $today->copy()->subMonths(8),
                'lease_end_date' => $today->copy()->addDays(60), // Expires in 60 days
                'monthly_rent' => 18000.00,
            ],
            [
                'name' => 'Anna Garcia',
                'email' => 'anna@example.com',
                'phone' => '+63 912 345 6791',
                'apartment' => $createdApartments[2],
                'lease_start_date' => $today->copy()->subMonths(6),
                'lease_end_date' => $today->copy()->addDays(90), // Expires in 90 days
                'monthly_rent' => 20000.00,
            ],
            [
                'name' => 'Carlos Rodriguez',
                'email' => 'carlos@example.com',
                'phone' => '+63 912 345 6792',
                'apartment' => $createdApartments[3],
                'lease_start_date' => $today->copy()->subMonths(12),
                'lease_end_date' => $today->copy()->addDays(120), // Expires in 120 days (no notification)
                'monthly_rent' => 22000.00,
            ],
        ];

        $createdTenants = [];
        foreach ($tenantsData as $tenantData) {
            $apartment = $tenantData['apartment'];
            $tenant = Tenant::firstOrCreate(
                [
                    'apartment_id' => $apartment->id,
                    'owner_id' => $owner->id,
                    'email' => $tenantData['email'],
                ],
                [
                    'apartment_id' => $apartment->id,
                    'owner_id' => $owner->id,
                    'name' => $tenantData['name'],
                    'email' => $tenantData['email'],
                    'phone' => $tenantData['phone'],
                    'move_in_date' => $tenantData['lease_start_date'],
                    'lease_start_date' => $tenantData['lease_start_date'],
                    'lease_end_date' => $tenantData['lease_end_date'],
                    'monthly_rent' => $tenantData['monthly_rent'],
                    'status' => 'active',
                    'deposit_amount' => $tenantData['monthly_rent'] * 2,
                ]
            );
            $createdTenants[] = $tenant;
            $this->command->info("✓ Created tenant: {$tenant->name} (Lease expires: {$tenant->lease_end_date->format('M d, Y')})");
        }

        // Step 5: Create rent payments - some overdue, some pending
        $paymentData = [
            [
                'tenant' => $createdTenants[0], // Maria Santos
                'apartment' => $createdApartments[0],
                'amount' => 15000.00,
                'due_date' => $today->copy()->subDays(15), // 15 days overdue
                'status' => 'overdue',
            ],
            [
                'tenant' => $createdTenants[0], // Maria Santos - another payment
                'apartment' => $createdApartments[0],
                'amount' => 15000.00,
                'due_date' => $today->copy()->subDays(5), // 5 days overdue
                'status' => 'overdue',
            ],
            [
                'tenant' => $createdTenants[1], // Juan Dela Cruz
                'apartment' => $createdApartments[1],
                'amount' => 18000.00,
                'due_date' => $today->copy()->subDays(10), // 10 days overdue
                'status' => 'overdue',
            ],
            [
                'tenant' => $createdTenants[2], // Anna Garcia
                'apartment' => $createdApartments[2],
                'amount' => 20000.00,
                'due_date' => $today->copy()->addDays(5), // Due in 5 days (not overdue yet)
                'status' => 'pending',
            ],
            [
                'tenant' => $createdTenants[3], // Carlos Rodriguez
                'apartment' => $createdApartments[3],
                'amount' => 22000.00,
                'due_date' => $today->copy()->subDays(3), // 3 days overdue
                'status' => 'overdue',
            ],
        ];

        foreach ($paymentData as $payment) {
            $rentPayment = RentPayment::firstOrCreate(
                [
                    'tenant_id' => $payment['tenant']->id,
                    'apartment_id' => $payment['apartment']->id,
                    'due_date' => $payment['due_date'],
                ],
                [
                    'tenant_id' => $payment['tenant']->id,
                    'apartment_id' => $payment['apartment']->id,
                    'amount' => $payment['amount'],
                    'due_date' => $payment['due_date'],
                    'status' => $payment['status'],
                ]
            );
            
            $daysOverdue = $payment['due_date']->isPast() 
                ? $today->diffInDays($payment['due_date']) 
                : 0;
            
            $statusText = $payment['status'] === 'overdue' 
                ? "({$daysOverdue} days overdue)" 
                : "(due in " . $today->diffInDays($payment['due_date']) . " days)";
            
            $this->command->info("✓ Created rent payment: ₱" . number_format($payment['amount'], 2) . " for {$payment['tenant']->name} - {$statusText}");
        }

        // Step 6: Create sample notifications to demonstrate the system
        // These would normally be created by the scheduled command, but we'll create them manually for demo
        
        // Overdue payment notifications
        $overdueNotifications = [
            [
                'type' => 'overdue_payment',
                'title' => 'Overdue Payment: Maria Santos',
                'message' => 'Payment of ₱15,000.00 for Sunset Apartments (Unit: 101) was due on ' . 
                            $today->copy()->subDays(15)->format('M d, Y') . '. It is now 15 day(s) overdue.',
            ],
            [
                'type' => 'overdue_payment',
                'title' => 'Overdue Payment: Maria Santos',
                'message' => 'Payment of ₱15,000.00 for Sunset Apartments (Unit: 101) was due on ' . 
                            $today->copy()->subDays(5)->format('M d, Y') . '. It is now 5 day(s) overdue.',
            ],
            [
                'type' => 'overdue_payment',
                'title' => 'Overdue Payment: Juan Dela Cruz',
                'message' => 'Payment of ₱18,000.00 for Sunset Apartments (Unit: 202) was due on ' . 
                            $today->copy()->subDays(10)->format('M d, Y') . '. It is now 10 day(s) overdue.',
            ],
            [
                'type' => 'overdue_payment',
                'title' => 'Overdue Payment: Carlos Rodriguez',
                'message' => 'Payment of ₱22,000.00 for Ocean View Residences (Unit: 402) was due on ' . 
                            $today->copy()->subDays(3)->format('M d, Y') . '. It is now 3 day(s) overdue.',
            ],
        ];

        // Lease expiration notifications
        $leaseNotifications = [
            [
                'type' => 'lease_expiration',
                'title' => 'Lease Expiring in 30 Days: Maria Santos',
                'message' => 'The lease for Maria Santos at Sunset Apartments (Unit: 101) will expire on ' . 
                            $today->copy()->addDays(30)->format('M d, Y') . '. Please prepare for renewal or move-out procedures.',
            ],
            [
                'type' => 'lease_expiration',
                'title' => 'Lease Expiring in 60 Days: Juan Dela Cruz',
                'message' => 'The lease for Juan Dela Cruz at Sunset Apartments (Unit: 202) will expire on ' . 
                            $today->copy()->addDays(60)->format('M d, Y') . '. Please prepare for renewal or move-out procedures.',
            ],
            [
                'type' => 'lease_expiration',
                'title' => 'Lease Expiring in 90 Days: Anna Garcia',
                'message' => 'The lease for Anna Garcia at Ocean View Residences (Unit: 301) will expire on ' . 
                            $today->copy()->addDays(90)->format('M d, Y') . '. Please prepare for renewal or move-out procedures.',
            ],
        ];

        // Create unread notifications
        foreach ($overdueNotifications as $notification) {
            Notification::create([
                'user_id' => $owner->id,
                'type' => $notification['type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'read_at' => null, // Unread
                'created_at' => $today->copy()->subHours(2), // Created 2 hours ago
            ]);
        }

        // Create one read notification to show the difference
        Notification::create([
            'user_id' => $owner->id,
            'type' => 'overdue_payment',
            'title' => 'Overdue Payment: Carlos Rodriguez',
            'message' => 'Payment of ₱22,000.00 for Ocean View Residences (Unit: 402) was due on ' . 
                        $today->copy()->subDays(3)->format('M d, Y') . '. It is now 3 day(s) overdue.',
            'read_at' => $today->copy()->subHours(1), // Read 1 hour ago
            'created_at' => $today->copy()->subDays(1), // Created yesterday
        ]);

        // Create lease expiration notifications (all unread)
        foreach ($leaseNotifications as $notification) {
            Notification::create([
                'user_id' => $owner->id,
                'type' => $notification['type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'read_at' => null, // Unread
                'created_at' => $today->copy()->subHours(1), // Created 1 hour ago
            ]);
        }

        $this->command->info("✓ Created " . count($overdueNotifications) . " overdue payment notifications");
        $this->command->info("✓ Created " . count($leaseNotifications) . " lease expiration notifications");
        $this->command->info("✓ Created 1 read notification (for demonstration)");

        $totalUnread = Notification::where('user_id', $owner->id)->whereNull('read_at')->count();
        $this->command->info("\n📊 Summary:");
        $this->command->info("   - Total unread notifications: {$totalUnread}");
        $this->command->info("   - Owner email: {$owner->email}");
        $this->command->info("   - Owner password: password");
        $this->command->info("\n✅ Demo data created successfully!");
        $this->command->info("   Login as {$owner->email} to see the notifications in the dashboard.");
    }
}
