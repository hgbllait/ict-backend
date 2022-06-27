<?php

namespace App\Data\Models\Auth;

use App\Data\Models\AuthBaseModel;
use App\Data\Models\Definition\UserAssign;
use App\Data\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends AuthBaseModel
{

    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    /**
     * database table name
     */
    protected $table = 'users';

    /**
     * list of fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'employee_id',
        'email',
        'email_verified_at',
        'password',
        'status',
    ];

    /**
     * list of searchable fields
     *
     * @var array
     */
    protected $searchable = [
        'id',
        'username',
        'employee_id',
        'email',
        'email_verified_at',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public $timestamps = true;

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function employee(){
        return $this->belongsTo(
            Employee::class,
            "employee_id",
            "id"
        );
    }

    public function approver(){
        return $this->belongsTo(
            UserAssign::class,
            "id",
            "user_id"
        );
    }

    public function logs(){
        return $this->hasMany(
            Logs::class,
            "user_id",
            "id"
        )->orderBy('id', 'DESC');
    }
}
