<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeWorkLog extends Model
{
    protected $primaryKey = 'workLog_id';

    protected $fillable = [
        'schedule_id',
        'employee_id',
        'actual_work_start',
        'actual_work_end',
        'total_work_hours',
        'remarks',
        'gps_latitude',
        'gps_longitude',
        'approval_status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    public function schedule()
    {
        return $this->belongsTo(ServiceSchedule::class, 'schedule_id');
    }

    public function employee()
    {
        return $this->belongsTo(EmployeeInfo::class, 'employee_id');
    }
}
