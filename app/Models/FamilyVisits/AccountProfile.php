<?php

namespace App\Models\FamilyVisits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property string $id
 * @property string $full_name
 */
class AccountProfile extends Model
{
    protected $table = 'profiles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['full_name', 'phone'];

    /** @return HasMany<VisitRequest, $this> */
    public function requestedVisits(): HasMany
    {
        return $this->hasMany(VisitRequest::class, 'requester_id');
    }

    /** @return HasMany<VisitRequest, $this> */
    public function reviewedVisits(): HasMany
    {
        return $this->hasMany(VisitRequest::class, 'reviewed_by');
    }
}
