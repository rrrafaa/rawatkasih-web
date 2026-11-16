<?php

namespace App\Models;

class Incident extends SupabaseModel
{
    protected string $table = 'incidents';

    protected string $columns = 'id,resident_id,reporter_id,category,severity,description,action_taken,occurred_at,status,follow_up,resolved_at';
}
