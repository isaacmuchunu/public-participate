<?php

namespace App\Policies;

use App\Models\SubmissionDraft;
use App\Models\User;

class SubmissionDraftPolicy
{
    /**
     * Determine if the user can view the draft
     */
    public function view(User $user, SubmissionDraft $draft): bool
    {
        // Users can only view their own drafts, except admins
        return $user->id === $draft->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can create drafts
     */
    public function create(User $user): bool
    {
        // Citizens and admins can create drafts
        return $user->isCitizen() || $user->isAdmin();
    }

    /**
     * Determine if the user can update the draft
     */
    public function update(User $user, SubmissionDraft $draft): bool
    {
        // Users can only update their own drafts, except admins
        return $user->id === $draft->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can delete the draft
     */
    public function delete(User $user, SubmissionDraft $draft): bool
    {
        // Users can only delete their own drafts, except admins
        return $user->id === $draft->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can submit the draft
     */
    public function submit(User $user, SubmissionDraft $draft): bool
    {
        // Users can only submit their own drafts, except admins
        return $user->id === $draft->user_id || $user->isAdmin();
    }
}
