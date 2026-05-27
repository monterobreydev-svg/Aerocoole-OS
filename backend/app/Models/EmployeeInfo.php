<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeInfo extends Model
{
    protected $primaryKey = 'employee_id';

    protected $fillable = [
        'employeeAccount_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'position',
        'hire_date'
    ];

    public function account()
    {
        return $this->belongsTo(EmployeeAccount::class, 'employeeAccount_id');
    }

    public function schedules()
    {
        return $this->hasMany(ServiceSchedule::class, 'employee_id');
    }

    public function workLogs()
    {
        return $this->hasMany(EmployeeWorkLog::class, 'employee_id');
    }
}
