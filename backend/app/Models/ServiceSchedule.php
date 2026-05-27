<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceSchedule extends Model
{
    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'branch_id',
        'employee_id',
        'service_type',
        'description',
        'schedule_start',
        'estimated_end',
        'status',
        'reschedule_reason',
        'created_by'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function employee()
    {
        return $this->belongsTo(EmployeeInfo::class, 'employee_id');
    }

    public function workLogs()
    {
        return $this->hasMany(EmployeeWorkLog::class, 'schedule_id');
    }
}
