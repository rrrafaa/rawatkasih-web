<?php

namespace App\Models;

class CareTaskOccurrence extends SupabaseModel
{
    protected string $table = 'care_task_occurrences';

    protected string $columns = 'id,template_id,task_date,status,reason,completed_by,completed_at';
}
