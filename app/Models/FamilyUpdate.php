<?php

namespace App\Models;

class FamilyUpdate extends SupabaseModel
{
    protected string $table = 'family_updates';

    protected string $columns = 'id,resident_id,summary,status,published_at';
}
