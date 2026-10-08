<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    /** Ver un lead: con leads.view_all cualquiera; con leads.view solo los asignados. */
    public function view(User $user, Lead $lead): bool
    {
        if ($user->hasPermission('leads.view_all')) {
            return true;
        }

        return $user->hasPermission('leads.view') && $lead->assigned_to === $user->id;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.update') && $this->view($user, $lead);
    }

    public function move(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.move') && $this->view($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.delete') && $this->view($user, $lead);
    }

    public function note(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.notes') && $this->view($user, $lead);
    }
}
