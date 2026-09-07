<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelativeInfoUpdate extends Model
{
    protected $table = 'relative_info_updates';
    public $timestamps = false;

    protected $fillable = [
        'passenger_id', 'team_member_id', 'updated_by_name',
        'contact_name', 'relationship', 'telephone_numbers', 'address',
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
