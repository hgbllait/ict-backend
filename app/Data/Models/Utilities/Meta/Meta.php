<?php

namespace App\Data\Models\Utilities\Meta;

use App\Data\Models\BaseModel;

class Meta extends BaseModel
{
    protected $table = 'metas';

    protected $fillable = [
        'target_type',
        'target_id',
        'meta_key',
        'meta_value',
        'added_by',
        'updated_by'
    ];

    protected $searchable =[
        'id',
        'target_type',
        'target_id',
        'meta_key',
        'meta_value',
        'added_by',
        'updated_by'
    ];

    protected $attributes = [
        'added_by' => 0,
        'updated_by' => 0
    ];
}
