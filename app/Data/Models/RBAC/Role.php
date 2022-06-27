<?php

namespace App\Data\Models\RBAC;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole {

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

}
