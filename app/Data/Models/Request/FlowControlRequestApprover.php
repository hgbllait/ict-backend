<?php

namespace App\Data\Models\Request;

use App\Data\Models\BaseModel;
use App\Data\Models\Definition\Approver;
use App\Data\Models\Definition\UserAssign;

class FlowControlRequestApprover extends BaseModel
{

    protected $table = 'flow_control_request_approvers';

    protected $attributes = [
        'added_by' => 0,
        'updated_by' => 0
    ];

    protected $fillable = [
      'name',
      'approver_id',
      'flow_control_request_id',
      'approval_status',
      'override_reject',
      'override_accept',
      'required',
      'added_by',
      'updated_by',
  ];

  protected $searchable =[
      'id',
      'name',
      'approver_id',
      'flow_control_request_id',
      'approval_status',
      'override_reject',
      'override_accept',
      'required',
      'added_by',
      'updated_by',
  ];

    public function approver(){
        return $this->belongsTo(
            Approver::class,
            "approver_id",
            "id"
        );
    }

    public function flowControl(){
        return $this->belongsTo(
            FlowControlRequest::class,
            "flow_control_request_id",
            "id"
        );
    }

    public function user(){
        return $this->belongsTo(
            UserAssign::class,
            "approver_id",
            "approver_id"
        );
    }
}
