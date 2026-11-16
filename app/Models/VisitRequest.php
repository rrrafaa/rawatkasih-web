<?php

namespace App\Models;

class VisitRequest extends SupabaseModel
{
    protected string $table = 'visit_requests';

    protected string $columns = 'id,resident_id,requester_id,requested_at,visitor_count,note,status,reviewed_at,review_note';
}
