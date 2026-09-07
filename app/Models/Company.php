<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $table = 'companies';
    public $timestamps = false;   // only created_at, managed by DB default
    protected $hidden = ['password_hash', 'remember_token'];

    protected $fillable = ['name', 'email', 'password_hash', 'logo_url'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }
}
