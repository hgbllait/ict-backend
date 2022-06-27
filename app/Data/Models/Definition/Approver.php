<?php

namespace App\Data\Models\Definition;

use App\Data\Models\BaseModel;

class Approver extends BaseModel
{
  protected $table = 'approvers';

  protected $attributes = [
      'added_by' => 0,
      'updated_by' => 0
  ];

  protected $fillable = [
      'employee_id',
      'name',
      'approver_unique_id',
      'email',
      'type',
      'description',
      'status',
      'added_by',
      'updated_by',
  ];

  protected $searchable =[
      'id',
      'employee_id',
      'name',
      'approver_unique_id',
      'email',
      'type',
      'description',
      'status',
      'added_by',
      'updated_by',
  ];



    public function assign(){
        return $this->belongsTo(
            UserAssign::class,
            "id",
            "approver_id"
        );
    }
}
