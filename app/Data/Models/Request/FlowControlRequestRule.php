<?php

namespace App\Data\Models\Request;

use App\Data\Models\BaseModel;
use App\Data\Models\Definition\Rule;

class FlowControlRequestRule extends BaseModel
{

    protected $table = 'flow_control_request_rules';

    protected $fillable = [
        'name',
        'flow_control_request_id',
        'rule_id',
        'added_by',
        'updated_by',
    ];

    protected $searchable =[
        'id',
        'name',
        'flow_control_request_id',
        'rule_id',
        'added_by',
        'updated_by',
    ];

    public function rule(){
        return $this->belongsTo(
            Rule::class,
            "rule_id",
            "id"
        );
    }
}
