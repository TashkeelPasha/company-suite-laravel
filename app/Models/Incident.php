<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    public const STATUS_ONGOING = 'Ongoing';
    public const STATUS_COMPLETED = 'Operation Completed';

    protected $table = 'incidents';
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'flight_number', 'from_location', 'to_location',
        'incident_date', 'incident_time', 'status',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }
}
