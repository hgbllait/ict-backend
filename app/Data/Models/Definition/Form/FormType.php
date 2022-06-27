<?php

namespace App\Data\Models\Definition\Form;

use App\Data\Models\BaseModel;

class FormType extends BaseModel
{
    protected $table = 'form_types';

    protected $attributes = [
        'added_by' => 0,
        'updated_by' => 0
    ];

    protected $fillable = [
        'name',
        'form_no',
        'revision_no',
        'issue_no',
        'type',
        'date_effective',
        'description',
        'added_by',
        'updated_by'
    ];

    protected $searchable = [
        'name',
        'form_no',
        'revision_no',
        'issue_no',
        'date_effective',
        'type',
        'description',
        'added_by',
        'updated_by'
    ];

}
