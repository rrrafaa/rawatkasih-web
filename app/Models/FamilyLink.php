<?php

namespace App\Models;

class FamilyLink extends SupabaseModel
{
    protected string $table = 'family_links';

    protected string $columns = 'id,user_id,resident_id,relationship,status,verified_at';
}
