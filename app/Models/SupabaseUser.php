<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Auth\GenericUser;

/** Auth identity from auth.users + the RLS-protected public.profiles row. */
class SupabaseUser extends GenericUser
{
    public function role(): Role
    {
        return Role::from($this->attributes['role']);
    }

    public function name(): string
    {
        return $this->attributes['full_name'];
    }

    public function id(): string
    {
        return $this->attributes['id'];
    }
}
