<?php

namespace App\Data\Models\Definition\Form;

use App\Data\Models\BaseModel;
use App\Data\Models\Request\FlowControlRequest;

class Form extends BaseModel
{
    protected $table = 'forms';

    protected $attributes = [
        'added_by' => 0,
        'updated_by' => 0
    ];

    protected $fillable = [
        'name',
        'description',
        'revision_no',
        'issue_no',
        'date_effective',
        'form_type',
        'form_link',
        'type_id',
        'added_by',
        'updated_by'
    ];

    protected $searchable = [
        'name',
        'description',
        'revision_no',
        'issue_no',
        'date_effective',
        'form_type',
        'form_link',
        'type_id',
        'added_by',
        'updated_by'
    ];

    public function type(){
        return $this->belongsTo(
            FormType::class,
            "type_id",
            "id"
        );
    }

    public function flowControl(){
        return $this->belongsTo(
            FlowControlRequest::class,
            "id",
            "form_id"
        );
    }
}
