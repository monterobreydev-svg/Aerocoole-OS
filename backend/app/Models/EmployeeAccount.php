<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAccount extends Model
{
    protected $primaryKey = 'employeeAccount_id';

    protected $fillable = [
        'username',
        'password_hash',
        'role',
        'status',
        'last_login'
    ];

    public function employeeInfo()
    {
        return $this->hasOne(EmployeeInfo::class, 'employeeAccount_id');
    }
}
