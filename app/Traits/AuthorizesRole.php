<?php

namespace App\Traits;

trait AuthorizesRole
{
    protected function authorizeRole(string $role): void
    {
        if (!auth()->check()) {
            abort(403, 'You must be logged in');
        }

        if (auth()->user()->role !== $role) {
            abort(403, 'Unauthorized access');
        }
    }

    protected function authorizeOwnerOrAdmin(): void
    {
        if (!auth()->check()) {
            abort(403, 'You must be logged in');
        }

        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'owner'])) {
            abort(403, 'Unauthorized access');
        }
    }
}
