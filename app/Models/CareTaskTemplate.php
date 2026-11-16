<?php

namespace App\Models;

class CareTaskTemplate extends SupabaseModel
{
    protected string $table = 'care_task_templates';

    protected string $columns = 'id,resident_id,title,category,scheduled_time,instructions,is_active,frequency,days_of_week';
}
