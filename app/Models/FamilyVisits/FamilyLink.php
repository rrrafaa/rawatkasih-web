<?php

namespace App\Models\FamilyVisits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property string $user_id
 * @property string $status
 * @property string $relationship
 */
class FamilyLink extends Model
{
    protected $table = 'family_links';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['relationship'];

    /** @return BelongsTo<Resident, $this> */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'resident_id');
    }

    /** @return BelongsTo<AccountProfile, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AccountProfile::class, 'user_id');
    }
}
