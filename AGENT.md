# Agent Instructions

This document provides instructions and guidelines for AI agents working on this codebase.

## Technology Stack

### Backend Framework
- **Laravel Framework v12.0** - Use Laravel 12 conventions and features
- **PHP 8.2+** - All code must be compatible with PHP 8.2 or higher. Use modern PHP features (typed properties, attributes, match expressions, etc.)

### Frontend Stack
- **Livewire 4.0** - Use Livewire 4.0 syntax and patterns. Prefer single-file components (SFC) where appropriate. Use Livewire attributes like `#[Rule()]` for validation
- **Mary UI v2.6** (robsontenorio/mary) - Use Mary UI components (x-header, x-form, x-table, x-button, etc.) instead of building custom components from scratch
- **Vite v7.0.7** - Use Vite for asset bundling. Reference assets with `@vite()` directive. Do not use Laravel Mix
- **Tailwind CSS v4.1.18** - Use Tailwind CSS v4 syntax (`@import 'tailwindcss'`). Prefer utility classes over custom CSS
- **DaisyUI v5.5.14** - Use DaisyUI component classes (btn-primary, card, table, etc.) when styling. DaisyUI provides semantic component classes
- **Axios v1.11.0** - Use Axios for any HTTP client needs

### Development Tools
- **Laravel Tinker** - Available for REPL interactions during development
- **Laravel Sail** - Docker development environment available via `./vendor/bin/sail`
- **Laravel Pint** - Code style is enforced via Laravel Pint. Follow PSR-12 standards
- **Laravel Pail** - Real-time log viewer available for debugging
- **PHPUnit** - Write tests using PHPUnit. Test files should be in `tests/Feature/` or `tests/Unit/`
- **Faker** - Use Faker for generating test data in factories and seeders

## Coding Standards & Conventions

### PHP Code
- Follow PSR-12 coding standards
- Use type hints for all parameters, return types, and properties
- Use PHP 8.2+ attributes for metadata (e.g., `#[Rule()]` for Livewire validation)
- Prefer named parameters when they improve readability
- Use strict types: `declare(strict_types=1);`

### Livewire Components
- Prefer single-file components (SFC) with `⚡` prefix in Blade templates
- Use Livewire attributes for validation: `#[Rule('required|email')]`
- Use Mary UI Toast trait for notifications: `use Mary\Traits\Toast;`
- Use Livewire's `WithPagination` trait for paginated data
- Follow the pattern of defining component logic in PHP class within the Blade file

### Blade Templates
- Use Mary UI components: `<x-header>`, `<x-form>`, `<x-table>`, `<x-button>`, etc.
- Use Livewire directives: `wire:model`, `wire:submit`, `wire:click`
- Follow responsive design patterns with Tailwind classes (lg:, md:, etc.)
- Use DaisyUI semantic classes for consistent styling

### Styling
- Prioritize Tailwind utility classes over custom CSS
- Use DaisyUI component classes when available
- Use Mary UI components for consistent UI patterns
- Only add custom CSS when Tailwind/DaisyUI/Mary UI cannot achieve the desired result
- Keep custom CSS minimal and well-documented

### Database
- Use Eloquent ORM for database interactions
- Define relationships in models using proper type hints
- Use model factories for test data generation
- Use seeders for reference data

### Testing
- Write feature tests for user-facing functionality
- Write unit tests for business logic
- Use factories to create test data
- Follow AAA pattern (Arrange, Act, Assert)
- Ensure tests are isolated and can run independently

## Project Structure Patterns

### Routes
- Use `Route::livewire()` for Livewire component routes
- Keep routes organized and documented with comments

### Models
- Define relationships with proper return type hints
- Use `protected $guarded` or `protected $fillable` appropriately
- Implement `casts()` method for attribute casting
- Use Eloquent query scopes for reusable queries

### Components
- Single-file components (SFC) are preferred for simple components
- Place SFC components in `resources/views/pages/` directory
- Use `⚡` prefix for Livewire SFC components

### Assets
- Place CSS in `resources/css/app.css`
- Place JavaScript in `resources/js/app.js`
- Reference assets in Blade using `@vite(['resources/css/app.css', 'resources/js/app.js'])`

## Important Notes

- Do not use Laravel Mix - this project uses Vite
- Do not create custom components when Mary UI provides equivalent functionality
- Do not write custom CSS when Tailwind utilities can achieve the same result
- Maintain responsive design patterns for mobile-first development
- Use Livewire 4.0 syntax, not older versions

## Development Commands

- Setup: `composer setup` - Full application setup
- Development: `composer dev` - Start concurrent dev environment
- Testing: `composer test` or `php artisan test`
- Code Style: `./vendor/bin/pint`
