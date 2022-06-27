<?php

namespace App\Data\Models\Definition\Equipment;

use App\Data\Models\BaseModel;

class Equipment extends BaseModel
{
    protected $table = 'equipments';

    protected $attributes = [
        'added_by' => 0,
        'updated_by' => 0
    ];

    protected $fillable = [
        'alias',
        'equipment',
        'serial_no',
        'brand',
        'location',
        'details',
        'added_by',
        'updated_by'
    ];

    protected $searchable = [
        'id',
        'alias',
        'equipment',
        'serial_no',
        'brand',
        'location',
        'details',
        'added_by',
        'updated_by'
    ];

}
