<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Authentication Routes (Guest only)
Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login');              // Login
    Route::livewire('/register', 'pages::auth.register');    // Register
});

// Logout Route
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->middleware('auth')->name('logout');

// Home
Route::livewire('/', 'pages::index');                          // Home 
Route::livewire('/users', 'pages::users.index');               // User (list) 
Route::livewire('/users/create', 'pages::users.create');       // User (create) 
Route::livewire('/users/{user}/edit', 'pages::users.edit');    // User (edit) 

// Admin Dashboard
Route::livewire('/admin/dashboard', 'pages::admin.dashboard')->middleware('role:admin');         // Admin Dashboard

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
Route::livewire('/reports/tenant-turnover', 'pages::reports.tenant-turnover')->middleware('role:owner'); // Tenant Turnover Report 
