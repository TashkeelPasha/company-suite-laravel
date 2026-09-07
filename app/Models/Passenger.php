<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Passenger extends Model
{
    protected $table = 'passengers';
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'incident_id', 'name', 'seat_number', 'cnic',
        'flight_number', 'from_location', 'to_location',
        'departure_time', 'arrival_time',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(PassengerUpdate::class);
    }

    public function relativeInfoUpdates(): HasMany
    {
        return $this->hasMany(RelativeInfoUpdate::class);
    }
}
