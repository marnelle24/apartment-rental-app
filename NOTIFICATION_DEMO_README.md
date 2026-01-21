# Notification System Demo Data

This document explains how to create and use dummy data to demonstrate the notification system.

## Overview

The notification system automatically creates notifications for:
1. **Overdue Payments** - When rent payments are past their due date
2. **Lease Expirations** - When leases are expiring in 30, 60, or 90 days

## Step-by-Step: What the Seeder Does

### Step 1: Create Owner User
- Creates a demo owner user with:
  - Email: `owner@demo.com`
  - Password: `password`
  - Name: "John Property Owner"
  - Role: `owner`

### Step 2: Create Location
- Creates a location: "Manila City"
- This is required for apartments

### Step 3: Create Apartments
- Creates 4 apartments:
  - Sunset Apartments (Units 101, 202)
  - Ocean View Residences (Units 301, 402)
- All apartments are set to "occupied" status
- Each has different monthly rent amounts

### Step 4: Create Tenants
- Creates 4 tenants with different lease expiration dates:
  - **Maria Santos** - Lease expires in **30 days** (will trigger notification)
  - **Juan Dela Cruz** - Lease expires in **60 days** (will trigger notification)
  - **Anna Garcia** - Lease expires in **90 days** (will trigger notification)
  - **Carlos Rodriguez** - Lease expires in **120 days** (no notification)

### Step 5: Create Rent Payments
- Creates 5 rent payments:
  - **3 overdue payments** (15, 10, and 3 days overdue)
  - **1 pending payment** (due in 5 days)
  - These will trigger `overdue_payment` notifications

### Step 6: Create Sample Notifications
- Creates **4 unread overdue payment notifications**
- Creates **1 read overdue payment notification** (to show read/unread difference)
- Creates **3 unread lease expiration notifications** (30, 60, 90 days)
- Total: **7 unread notifications** will be visible in the UI

## How to Run the Seeder

### Option 1: Run Only the Notification Demo Seeder
```bash
php artisan db:seed --class=NotificationDemoSeeder
```

### Option 2: Add to DatabaseSeeder (Recommended)
Add this line to `database/seeders/DatabaseSeeder.php`:
```php
$this->call(NotificationDemoSeeder::class);
```

Then run:
```bash
php artisan db:seed
```

## How to View the Notifications

1. **Login as the owner:**
   - Email: `owner@demo.com`
   - Password: `password`

2. **Navigate to Dashboard:**
   - Go to `/dashboard`
   - You'll see a bell icon in the header with a badge showing the unread count (7)

3. **View Notification Details:**
   - The dashboard shows:
     - Unread notification count in the header
     - Overdue payments alerts section
     - Lease expiring alerts section

## Testing the Notification System

### Manual Testing
You can manually trigger the notification check command:
```bash
php artisan notifications:check
```

This will:
- Check for overdue payments and create notifications
- Check for lease expirations and create notifications
- Prevent duplicates (won't create the same notification twice in one day)

### Scheduled Execution
The notifications are automatically checked daily at 8:00 AM (Asia/Manila timezone) via Laravel's task scheduler.

## Notification Types

### 1. Overdue Payment (`overdue_payment`)
- **Triggered when:** Payment due date has passed and status is not "paid"
- **Title format:** "Overdue Payment: {Tenant Name}"
- **Message includes:** Amount, apartment name, unit number, due date, days overdue

### 2. Lease Expiration (`lease_expiration`)
- **Triggered when:** Lease is expiring in exactly 30, 60, or 90 days
- **Title format:** "Lease Expiring in {X} Days: {Tenant Name}"
- **Message includes:** Tenant name, apartment name, unit number, expiration date

## User Types Who Can See Notifications

- ✅ **Owners** - Can see all notifications (as demonstrated)
- ❌ **Admins** - Cannot see these notifications (different dashboard)
- ❌ **Tenants** - Cannot see these notifications

## Database Structure

Notifications are stored in the `notifications` table:
- `user_id` - The owner who receives the notification
- `type` - Notification type (`overdue_payment` or `lease_expiration`)
- `title` - Notification title
- `message` - Full notification message
- `read_at` - Timestamp when marked as read (null = unread)
- `created_at` - When notification was created

## Notes

- Notifications are created **once per day** per entity to prevent spam
- The system checks if a notification already exists today before creating a new one
- Unread notifications are counted and displayed in the dashboard header
- The "View All Notifications" button currently links to `#` (needs implementation)
