<?php

namespace App\Data\Models\Definition;

use App\Data\Models\BaseModel;

class Rule extends BaseModel
{
  protected $table = 'rules';

    protected $attributes = [
        'added_by' => 0,
        'updated_by' => 0
    ];

  protected $fillable = [
      'employee_id',
      'rule_unique_id',
      'description',
      'status',
      'added_by',
      'updated_by',
  ];

  protected $searchable =[
      'id',
      'employee_id',
      'rule_unique_id',
      'description',
      'status',
      'added_by',
      'updated_by',
  ];
}
