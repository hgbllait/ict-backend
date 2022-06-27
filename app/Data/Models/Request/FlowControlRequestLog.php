<?php

namespace App\Data\Models\Request;

use App\Data\Models\BaseModel;

class FlowControlRequestLog extends BaseModel
{

    protected $table = 'flow_control_request_logs';

    protected $attributes = [
        'added_by' => 0,
        'updated_by' => 0
    ];

    protected $fillable = [
      'flow_control_request_id',
      'event_description',
      'added_by',
      'updated_by',
  ];

  protected $searchable =[
      'id',
      'employee_id',
      'flow_control_request_id',
      'event_description',
      'added_by',
      'updated_by',
  ];
}
