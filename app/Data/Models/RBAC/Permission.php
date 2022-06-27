<?php

namespace App\Data\Models\RBAC;

use App\Data\Models\Employee\Employee;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission {

    protected $fillable = [
        'name',
        'guard_name'
    ];

    protected $searchable = [
        'name',
        'guard_name'
    ];

    public function getClass()
    {
        return static::class;
    }

    public function searchableColumns()
    {
        return (array) $this->searchable;
    }

    public function isSearchable($target)
    {
        return in_array($target, $this->searchableColumns());
    }

    public function employee(){
        return $this->hasMany(
            Employee::class,
            "employee_id",
            "id"
        );
    }

}
