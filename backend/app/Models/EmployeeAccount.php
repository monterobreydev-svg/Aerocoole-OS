<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class EmployeeAccount extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $primaryKey = 'employeeAccount_id';

    protected $fillable = [
        'username',
        'password_hash',
        'role',
        'status',
        'last_login',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * Map 'password' attribute to 'password_hash' column for Laravel auth.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function employeeInfo()
    {
        return $this->hasOne(EmployeeInfo::class, 'employeeAccount_id');
    }
}
