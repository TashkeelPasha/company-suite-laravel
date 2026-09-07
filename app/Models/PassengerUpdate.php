<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassengerUpdate extends Model
{
    public const STATUS_SAFE = 'Safe';
    public const STATUS_INJURED = 'Injured';
    public const STATUS_CRITICAL = 'Critical';
    public const STATUS_DECEASED = 'Deceased';
    public const STATUS_UNCONFIRMED = 'Unconfirmed';

    public const ALL_STATUSES = [
        self::STATUS_SAFE, self::STATUS_INJURED, self::STATUS_CRITICAL,
        self::STATUS_DECEASED, self::STATUS_UNCONFIRMED,
    ];

    protected $table = 'passenger_updates';
    public $timestamps = false;

    protected $fillable = [
        'passenger_id', 'team_member_id', 'submitted_by', 'status', 'remarks',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(Passenger::class);
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }
}
