<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $primaryKey = 'branch_id';

    protected $fillable = [
        'client_id',
        'branch_name',
        'address',
        'latitude',
        'longitude',
        'status',
        'contact_person',
        'contact_number'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function schedules()
    {
        return $this->hasMany(ServiceSchedule::class, 'branch_id');
    }
}
