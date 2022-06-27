<?php

namespace App\Data\Models\Request;

use App\Data\Models\BaseModel;
use App\Data\Models\Definition\Form\Form;

class FlowControlRequest extends BaseModel
{
    protected $table = 'flow_control_requests';

    protected $attributes = [
        'added_by' => 0,
        'updated_by' => 0
    ];

    protected $fillable = [
        'form_id',
        'name',
        'approval_status',
        'added_by',
        'updated_by',
    ];

    protected $searchable = [
        'id',
        'form_id',
        'name',
        'approval_status',
        'added_by',
        'updated_by',
    ];

    public function approvers()
    {
        return $this->hasMany( 'App\Data\Models\Request\FlowControlRequestApprover' );
    }

    public function forms(){
        return $this->belongsTo(
            Form::class,
            "form_id",
            "id"
        );
    }

    public function logs(){
        return $this->hasMany( 'App\Data\Models\Request\FlowControlRequestLog' );
    }
}
