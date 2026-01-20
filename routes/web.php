<?php

// Route::livewire('/', 'pages::users.index');
Route::livewire('/', 'pages::index');                          // Home 
Route::livewire('/users', 'pages::users.index');               // User (list) 
Route::livewire('/users/create', 'pages::users.create');       // User (create) 
Route::livewire('/users/{user}/edit', 'pages::users.edit');    // User (edit) 
