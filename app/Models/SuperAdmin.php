<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\Model;

class SuperAdmin extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $table = 'super_admins';
    public $timestamps = false;
    protected $hidden = ['password_hash', 'remember_token'];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }
}
