<?php

namespace App\Models;

class Consent extends SupabaseModel
{
    protected string $table = 'consents';

    protected string $columns = 'id,resident_id,scope,status,recorded_at,revoked_at';

    public function hasPhotoConsent(string $residentId): bool
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $residentId)) {
            return false;
        }

        $rows = $this->read([
            'resident_id' => 'eq.'.$residentId,
            'scope' => 'eq.photo_share',
            'status' => 'eq.granted',
            'limit' => 1,
        ]);

        return count($rows) > 0;
    }

    public function hasVisibilityConsent(string $residentId): bool
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $residentId)) {
            return false;
        }

        $rows = $this->read([
            'resident_id' => 'eq.'.$residentId,
            'scope' => 'eq.family_visibility',
            'status' => 'eq.granted',
            'limit' => 1,
        ]);

        return count($rows) > 0;
    }
}
