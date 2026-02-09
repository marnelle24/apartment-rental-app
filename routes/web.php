<?php

use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Authentication Routes (Guest only)
// Rate limiting for login/register is applied in the Livewire components on form submit only,
// so viewing the page is not limited (avoids 429 when loading /login or /register).
Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');              // Login
    Route::livewire('/register', 'pages::auth.register')->name('register');    // Register
});

// Logout Route
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->middleware('auth')->name('logout');

// Home - Marketplace landing page (accessible to everyone)
Route::get('/', function () {
    return view('index');
})->name('home');

Route::livewire('/users', 'pages::users.index')->name('users.index')->middleware('role:admin');              // User (list) 
Route::livewire('/users/create', 'pages::users.create')->name('users.create')->middleware('role:admin');     // User (create) 
Route::livewire('/users/{user}/edit', 'pages::users.edit')->name('users.edit')->middleware('role:admin');    // User (edit) 

// Admin Dashboard
Route::livewire('/admin/dashboard', 'pages::admin.dashboard')->middleware('role:admin');         // Admin Dashboard

// Owner Monitoring (Admin only)
Route::livewire('/admin/owners', 'pages::admin.owners.index')->middleware('role:admin');         // Owner Monitoring (list)
Route::livewire('/admin/owners/{user}', 'pages::admin.owners.show')->middleware('role:admin');  // Owner Detail

// Tenant Monitoring (Admin only)
Route::livewire('/admin/tenants', 'pages::admin.tenants.index')->middleware('role:admin');         // Tenant Monitoring (list)
Route::livewire('/admin/tenants/{tenant}', 'pages::admin.tenants.show')->middleware('role:admin');  // Tenant Detail

// Apartment Monitoring (Admin only)
Route::livewire('/admin/apartments', 'pages::admin.apartments.index')->middleware('role:admin');         // Apartment Monitoring (list)
Route::livewire('/admin/apartments/{apartment}', 'pages::admin.apartments.show')->middleware('role:admin');  // Apartment Detail

// Plan Management (Admin only)
Route::livewire('/admin/plans', 'pages::admin.plans.index')->middleware('role:admin');                   // Plans (list)
Route::livewire('/admin/plans/{plan}/edit', 'pages::admin.plans.edit')->middleware('role:admin');        // Plan (edit)

// Owner Dashboard
Route::livewire('/dashboard', 'pages::dashboard.index')->middleware('role:owner');               // Owner Dashboard

// Locations (Admin only)
Route::livewire('/locations', 'pages::locations.index')->middleware('role:admin');               // Location (list) 
Route::livewire('/locations/create', 'pages::locations.create')->middleware('role:admin');       // Location (create) 
Route::livewire('/locations/{location}/edit', 'pages::locations.edit')->middleware('role:admin');    // Location (edit) 

// Apartments (Owner only)
Route::livewire('/apartments', 'pages::apartments.index')->middleware('role:owner');               // Apartment (list) 
Route::livewire('/apartments/create', 'pages::apartments.create')->middleware('role:owner');       // Apartment (create) 
Route::livewire('/apartments/{apartment}', 'pages::apartments.show')->middleware('role:owner');    // Apartment (show) 
Route::livewire('/apartments/{apartment}/edit', 'pages::apartments.edit')->middleware('role:owner');    // Apartment (edit) 

// Tenants (Owner only)
Route::livewire('/tenants', 'pages::tenants.index')->middleware('role:owner');               // Tenant (list) 
Route::livewire('/tenants/create', 'pages::tenants.create')->middleware('role:owner');       // Tenant (create) 
Route::livewire('/tenants/{tenant}/edit', 'pages::tenants.edit')->middleware('role:owner');    // Tenant (edit)

// Rent Payments (Owner only)
Route::livewire('/rent-payments', 'pages::rent-payments.index')->middleware('role:owner');               // Rent Payment (list) 
Route::livewire('/rent-payments/create', 'pages::rent-payments.create')->middleware('role:owner');       // Rent Payment (create) 
Route::livewire('/rent-payments/{rentPayment}/edit', 'pages::rent-payments.edit')->middleware('role:owner');    // Rent Payment (edit)

// Reports (Owner only)
Route::livewire('/reports', 'pages::reports.index')->middleware('role:owner');                           // Reports (index)
Route::livewire('/reports/revenue', 'pages::reports.revenue')->middleware('role:owner');                 // Revenue Report
Route::livewire('/reports/occupancy', 'pages::reports.occupancy')->middleware('role:owner');             // Occupancy Report
Route::livewire('/reports/tenant-turnover', 'pages::reports.tenant-turnover')->name('reports.tenant-turnover')->middleware('role:owner'); // Tenant Turnover Report

// Notifications (Owner only)
Route::livewire('/notifications', 'pages::notifications.index')->middleware('role:owner');               // Notifications (list)

// Subscription / Billing (Owner only)
Route::livewire('/subscription/pricing', 'pages::subscription.pricing')->name('subscription.pricing')->middleware('role:owner');    // Pricing page
Route::livewire('/subscription/invoices', 'pages::subscription.invoices')->name('subscription.invoices')->middleware('role:owner');    // Invoice history
Route::livewire('/subscription/success', 'pages::subscription.success')->name('subscription.success')->middleware('role:owner');    // Success page after checkout
Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout')->middleware('role:owner');
Route::post('/subscription/swap', [SubscriptionController::class, 'swap'])->name('subscription.swap')->middleware('role:owner');
Route::get('/subscription/portal', [SubscriptionController::class, 'billingPortal'])->name('subscription.portal')->middleware('role:owner');
Route::get('/subscription/invoices/{id}/download', [SubscriptionController::class, 'downloadInvoice'])->name('subscription.invoice.download')->middleware('role:owner');

// Stripe Webhook (no CSRF, no auth — Stripe signs requests with webhook secret)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// Tenant Portal (Tenant only) - mobile-first, bottom nav
Route::middleware(['auth', 'role:tenant'])->group(function () {
    Route::livewire('/portal', 'pages::portal.dashboard')->name('portal.dashboard');
    Route::livewire('/portal/dashboard', 'pages::portal.dashboard');
    Route::livewire('/portal/apartments', 'pages::portal.apartments')->name('portal.apartments');
    Route::livewire('/portal/notifications', 'pages::portal.notifications')->name('portal.notifications');
    Route::livewire('/portal/profile', 'pages::portal.profile')->name('portal.profile');
});
