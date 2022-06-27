<?php

namespace App\Data\Models\Definition;

use App\Data\Models\BaseModel;

class JobOrderCount extends BaseModel
{
    protected $table = 'job_order_count';

    protected $fillable = [
        'count',
        'month',
        'year',
    ];

    protected $searchable =[
        'id',
        'count',
        'month',
        'year',
    ];

    public function getCountAttribute() {
        return sprintf("%04d", $this->attributes['count']);
    }

}
