<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    public const TYPE_STATION = 'Station Team';
    public const TYPE_ERC = 'ERC Team';
    public const TYPE_GO = 'GO Team';
    public const TYPE_SAT = 'SAT - Volunteers';

    public const ALL_TYPES = [
        self::TYPE_STATION,
        self::TYPE_ERC,
        self::TYPE_GO,
        self::TYPE_SAT,
    ];

    protected $table = 'team_members';
    public $timestamps = false;
    protected $hidden = ['password_hash', 'remember_token'];

    protected $fillable = ['company_id', 'full_name', 'email', 'password_hash', 'team_type'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
