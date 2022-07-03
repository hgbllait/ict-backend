<?php

namespace App\Data\Models\Employee;

use App\Data\Models\BaseModel;

class Employee extends BaseModel
{
    protected $primaryKey = 'id';
    protected $table = 'employees';
    protected $searchable = [
        'id',
        'full_name',
        'first_name',
        'last_name',
        'address',
        'email',
        'position',
        'contact_number',
        'status',
        'added_by',
        'updated_by'
    ];

    protected $fillable = array(
        'address',
        'contact_number',
        'email',
        'position',
        'first_name',
        'full_name',
        'last_name',
        'status',
        'added_by',
        'updated_by'
    );

    protected $attributes = [
        'added_by' => 0,
        'updated_by' => 0
    ];

    public $timestamps = true;

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

}
