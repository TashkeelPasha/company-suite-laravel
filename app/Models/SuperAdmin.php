<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class SuperAdmin extends Model implements Authenticatable
{
    use AuthenticatableTrait, HasApiTokens;

    protected $table = 'super_admins';
    public $timestamps = false;
    protected $hidden = ['password_hash', 'remember_token'];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }
}
