<?php

namespace App\Models\FamilyVisits;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property string $id
 * @property string $full_name
 * @property string|null $room
 * @property string $status
 * @property CarbonImmutable|null $birth_date
 * @property FamilyLink $familyLink
 */
class Resident extends Model
{
    protected $table = 'residents';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['full_name', 'birth_date', 'room', 'status'];

    protected function casts(): array
    {
        return ['birth_date' => 'immutable_date'];
    }

    /** @return HasMany<VisitRequest, $this> */
    public function visits(): HasMany
    {
        return $this->hasMany(VisitRequest::class, 'resident_id');
    }

    /** @return HasMany<FamilyLink, $this> */
    public function familyLinks(): HasMany
    {
        return $this->hasMany(FamilyLink::class, 'resident_id');
    }
}
