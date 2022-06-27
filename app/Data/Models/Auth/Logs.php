<?php

namespace App\Data\Models\Auth;

use App\Data\Models\BaseModel;

class Logs extends BaseModel
{
    protected $primaryKey = 'id';
    protected $table = 'login_logs';
    protected $searchable = [
        'id',
        'user_id',
        'type',
    ];

    protected $fillable = [
        'user_id',
        'type',
    ];

    public $timestamps = true;

    protected $hidden = ['updated_at', 'deleted_at'];

    public function user(){
        return $this->belongsTo(
            User::class,
            "user_id",
            "id"
        );
    }

}
