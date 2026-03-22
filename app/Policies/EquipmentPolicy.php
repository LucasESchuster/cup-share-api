<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;

class EquipmentPolicy
{
    public function update(User $user, Equipment $equipment): bool
    {
        // Personal equipment: only owner can edit
        // Global equipment: only admin in the future (for now, no one)
        return $equipment->user_id !== null && $user->id === $equipment->user_id;
    }

    public function delete(User $user, Equipment $equipment): bool
    {
        return $equipment->user_id !== null && $user->id === $equipment->user_id;
    }
}
