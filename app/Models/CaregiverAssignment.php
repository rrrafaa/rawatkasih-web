<?php

namespace App\Models;

class CaregiverAssignment extends SupabaseModel
{
    protected string $table = 'caregiver_assignments';

    protected string $columns = 'id,user_id,resident_id,start_date,end_date';
}
