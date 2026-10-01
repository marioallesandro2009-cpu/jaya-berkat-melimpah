<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

/**
 * Leads hold personal data (name, email, phone, IP, user-agent), so the rule is written
 * explicitly instead of relying on "whoever reaches the panel": administrators only.
 * Nobody creates or edits a lead in the admin (they come from the public form).
 */
class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, ContactMessage $message): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ContactMessage $message): bool
    {
        // "Tandai dibaca" changes only the status; it is an administrator action.
        return $user->is_admin;
    }

    public function delete(User $user, ContactMessage $message): bool
    {
        return $user->is_admin;
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_admin;
    }
}
