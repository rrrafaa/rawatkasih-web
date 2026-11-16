<?php

namespace App\Models;

class Consent extends SupabaseModel
{
    protected string $table = 'consents';

    protected string $columns = 'id,resident_id,scope,status,recorded_at,revoked_at';
}
