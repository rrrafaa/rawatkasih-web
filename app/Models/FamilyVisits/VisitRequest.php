<?php

namespace App\Models\FamilyVisits;

use App\Enums\VisitStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property string $id
 * @property string $resident_id
 * @property string $requester_id
 * @property VisitStatus $status
 * @property CarbonImmutable $requested_at
 * @property CarbonImmutable|null $requested_end_at
 * @property CarbonImmutable|null $reviewed_at
 * @property int $visitor_count
 * @property string|null $primary_visitor_name
 * @property string|null $note
 * @property string|null $review_note
 * @property Resident|null $resident
 * @property AccountProfile|null $requester
 */
class VisitRequest extends Model
{
    protected $table = 'visit_requests';

    protected $keyType = 'string';

    public $incrementing = false;

    // Actor, status, review and ID fields can never be mass-assigned from a form.
    protected $fillable = ['requested_at', 'requested_end_at', 'visitor_count', 'primary_visitor_name', 'note'];

    protected function casts(): array
    {
        return ['status' => VisitStatus::class, 'requested_at' => 'immutable_datetime',
            'requested_end_at' => 'immutable_datetime', 'reviewed_at' => 'immutable_datetime', 'visitor_count' => 'integer'];
    }

    /** @return BelongsTo<Resident, $this> */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'resident_id');
    }

    /** @return BelongsTo<AccountProfile, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(AccountProfile::class, 'requester_id');
    }

    /** @return BelongsTo<AccountProfile, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(AccountProfile::class, 'reviewed_by');
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        // Implicit resource binding through the current user's JWT, not a privileged SQL connection.
        return app(FamilyVisitData::class)->findVisit((string) $value);
    }
}
