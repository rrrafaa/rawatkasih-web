<?php

namespace App\Models;

class DailyNote extends SupabaseModel
{
    protected string $table = 'daily_notes';

    protected string $columns = 'id,resident_id,caregiver_id,mood,meal_note,internal_note,recorded_at';
}
