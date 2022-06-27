<?php

namespace App\Data\Models\Definition;

use App\Data\Models\Auth\User;
use App\Data\Models\BaseModel;

class UserAssign extends BaseModel
{
    protected $table = 'user_assigns';

    protected $attributes = [
        'added_by' => 0,
        'updated_by' => 0
    ];

    protected $fillable = [
        'user_id',
        'approver_id',
        'added_by',
        'updated_by'
    ];

    protected $searchable =[
        'id',
        'user_id',
        'approver_id',
        'added_by',
        'updated_by'
    ];


    public function approver(){
        return $this->belongsTo(
            Approver::class,
            "approver_id",
            "id"
        );
    }

    public function user(){
        return $this->belongsTo(
            User::class,
            "user_id",
            "id"
        );
    }
}
