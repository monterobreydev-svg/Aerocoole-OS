<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $primaryKey = 'client_id';
    
    protected $fillable = [
        'client_name',
        'tin_number',
        'client_address',
        'contact_person',
        'contact_number',
        'email',
        'status'
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class, 'client_id');
    }
}
