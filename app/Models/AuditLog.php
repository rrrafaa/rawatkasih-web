<?php

namespace App\Models;

class AuditLog extends SupabaseModel
{
    protected string $table = 'audit_logs';

    protected string $columns = 'id,actor_id,action,target_table,target_id,occurred_at';

    protected string $keyType = 'bigint';
}
