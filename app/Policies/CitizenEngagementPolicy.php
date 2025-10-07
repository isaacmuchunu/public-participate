<?php

namespace App\Policies;

use App\Models\CitizenEngagement;
use App\Models\User;

class CitizenEngagementPolicy
{
    /**
     * Determine if the user can view the engagement
     */
    public function view(User $user, CitizenEngagement $engagement): bool
    {
        // Users can view engagements they sent or received, plus admins/clerks
        return $user->id === $engagement->sender_id
            || $user->id === $engagement->recipient_id
            || $user->isAdmin()
            || $user->isClerk();
    }

    /**
     * Determine if the user can view any engagements
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view their engagements
        return true;
    }

    /**
     * Determine if the user can create engagements
     */
    public function create(User $user): bool
    {
        // Only citizens can send engagement messages
        return $user->isCitizen();
    }

    /**
     * Determine if the user can delete the engagement
     */
    public function delete(User $user, CitizenEngagement $engagement): bool
    {
        // Only sender can delete, or admins
        return $user->id === $engagement->sender_id || $user->isAdmin();
    }

    /**
     * Determine if the user can mark as read
     */
    public function markAsRead(User $user, CitizenEngagement $engagement): bool
    {
        // Only recipient can mark as read
        return $user->id === $engagement->recipient_id;
    }
}
