<?php

namespace App\Models;

class Resident extends SupabaseModel
{
    protected string $table = 'residents';

    protected string $columns = 'id,full_name,room,status';
}
