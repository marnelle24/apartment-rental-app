# Step-by-Step: What I Did to Create Notification Demo Data

## Overview
I created a comprehensive seeder (`NotificationDemoSeeder`) that generates realistic dummy data to demonstrate the notification system. Here's exactly what was done:

---

## Step 1: Created the Seeder File
**File:** `database/seeders/NotificationDemoSeeder.php`

I created a new seeder class that systematically builds the data needed to demonstrate notifications.

---

## Step 2: Created Owner User
**What it does:**
- Creates a demo owner user with:
  - Email: `owner@demo.com`
  - Password: `password`
  - Name: "John Property Owner"
  - Role: `owner`

**Why:** Notifications are only visible to owners, so we need an owner account to log in and view them.

**Code:**
```php
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
```

---

## Step 3: Created Location
**What it does:**
- Creates a location: "Manila City"

**Why:** Apartments require a `location_id` foreign key, so we need at least one location.

**Code:**
```php
$location = Location::firstOrCreate(
    ['name' => 'Manila City'],
    [
        'name' => 'Manila City',
        'description' => 'Metro Manila, Philippines',
    ]
);
```

---

## Step 4: Created Apartments
**What it does:**
- Creates 4 apartments:
  - Sunset Apartments (Units 101, 202)
  - Ocean View Residences (Units 301, 402)
- All set to "occupied" status
- Different monthly rent amounts (₱15,000 to ₱22,000)

**Why:** We need apartments to assign tenants to, and tenants need apartments to create rent payments.

**Code:**
```php
$apartments = [
    ['name' => 'Sunset Apartments', 'unit_number' => '101', 'monthly_rent' => 15000.00],
    ['name' => 'Sunset Apartments', 'unit_number' => '202', 'monthly_rent' => 18000.00],
    // ... more apartments
];
```

---

## Step 5: Created Tenants with Different Lease Expiration Dates
**What it does:**
- Creates 4 tenants:
  - **Maria Santos** - Lease expires in **30 days** (triggers notification)
  - **Juan Dela Cruz** - Lease expires in **60 days** (triggers notification)
  - **Anna Garcia** - Lease expires in **90 days** (triggers notification)
  - **Carlos Rodriguez** - Lease expires in **120 days** (no notification - too far out)

**Why:** The notification system checks for leases expiring in exactly 30, 60, or 90 days. We need tenants with leases at these specific intervals.

**Code:**
```php
$tenantsData = [
    [
        'name' => 'Maria Santos',
        'lease_end_date' => $today->copy()->addDays(30), // 30 days
        // ...
    ],
    // ... more tenants
];
```

---

## Step 6: Created Rent Payments (Some Overdue)
**What it does:**
- Creates 5 rent payments:
  - **3 overdue payments** (15, 10, and 3 days overdue)
  - **1 pending payment** (due in 5 days - not overdue yet)
  - **1 more overdue payment** (3 days overdue)

**Why:** The notification system creates `overdue_payment` notifications for payments past their due date. We need overdue payments to demonstrate this.

**Code:**
```php
$paymentData = [
    [
        'tenant' => $createdTenants[0],
        'amount' => 15000.00,
        'due_date' => $today->copy()->subDays(15), // 15 days overdue
        'status' => 'overdue',
    ],
    // ... more payments
];
```

---

## Step 7: Created Sample Notifications
**What it does:**
- Creates **4 unread overdue payment notifications**
- Creates **1 read overdue payment notification** (to show read/unread difference)
- Creates **3 unread lease expiration notifications** (30, 60, 90 days)
- **Total: 7 unread notifications**

**Why:** These notifications demonstrate:
1. How notifications appear in the UI
2. The difference between read and unread notifications
3. Both notification types (overdue payments and lease expirations)

**Code:**
```php
// Overdue payment notifications
foreach ($overdueNotifications as $notification) {
    Notification::create([
        'user_id' => $owner->id,
        'type' => 'overdue_payment',
        'title' => $notification['title'],
        'message' => $notification['message'],
        'read_at' => null, // Unread
    ]);
}

// Lease expiration notifications
foreach ($leaseNotifications as $notification) {
    Notification::create([
        'user_id' => $owner->id,
        'type' => 'lease_expiration',
        'title' => $notification['title'],
        'message' => $notification['message'],
        'read_at' => null, // Unread
    ]);
}
```

---

## Step 8: Updated DatabaseSeeder
**What it does:**
- Added a commented line in `DatabaseSeeder.php` to optionally include the notification demo seeder

**Why:** Makes it easy to include the demo data when running `php artisan db:seed`

**Code:**
```php
// Uncomment the line below to seed notification demo data
// $this->call(NotificationDemoSeeder::class);
```

---

## Step 9: Created Documentation
**Files created:**
1. `NOTIFICATION_DEMO_README.md` - Comprehensive guide on how to use the seeder
2. `NOTIFICATION_DEMO_STEPS.md` - This file, explaining what was done step-by-step

**Why:** Documentation helps users understand:
- How to run the seeder
- What data is created
- How to view the notifications
- How the notification system works

---

## How to Use the Demo Data

### Run the Seeder
```bash
php artisan db:seed --class=NotificationDemoSeeder
```

### Login and View
1. Login with:
   - Email: `owner@demo.com`
   - Password: `password`

2. Navigate to `/dashboard`

3. You'll see:
   - Bell icon in header with badge showing **7 unread notifications**
   - Overdue payments alerts section
   - Lease expiring alerts section

### Test the Notification System
Run the notification check command manually:
```bash
php artisan notifications:check
```

This will check for new overdue payments and lease expirations and create notifications (preventing duplicates).

---

## Summary of Created Data

| Entity | Count | Details |
|--------|-------|---------|
| Owner User | 1 | owner@demo.com |
| Location | 1 | Manila City |
| Apartments | 4 | 2 buildings, 4 units |
| Tenants | 4 | With leases expiring 30, 60, 90, 120 days |
| Rent Payments | 5 | 4 overdue, 1 pending |
| Notifications | 8 | 7 unread, 1 read |

---

## Key Features Demonstrated

✅ **Overdue Payment Notifications**
- Shows when payments are past due
- Includes amount, apartment, unit, and days overdue

✅ **Lease Expiration Notifications**
- Shows when leases expire in 30, 60, or 90 days
- Includes tenant name, apartment, and expiration date

✅ **Read/Unread Status**
- Unread notifications show in the count badge
- Read notifications are marked with `read_at` timestamp

✅ **User Type Visibility**
- Only owners can see these notifications
- Admins and tenants have different dashboards

---

## Next Steps

To see the notifications in action:
1. Run the seeder: `php artisan db:seed --class=NotificationDemoSeeder`
2. Login as `owner@demo.com` / `password`
3. Visit `/dashboard`
4. Check the bell icon for unread notification count
5. View the alerts sections for overdue payments and expiring leases
