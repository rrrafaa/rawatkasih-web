<?php

namespace App\Models;

class FamilyUpdate extends SupabaseModel
{
    protected string $table = 'family_updates';

    protected string $columns = 'id,resident_id,author_id,title,category,summary,photo_path,photo_caption,status,published_at';
}
