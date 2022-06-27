<?php
namespace App\Data\Models\Utilities\Files;

use App\Data\Models\BaseModel;

class File extends BaseModel
{
    /**
    * database table name
    */
    protected $table = 'files';

    /**
     * list of fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'target_id',
        'target_type',
        'full_path',
        'added_by',
        'updated_by'
    ];

    /**
     * list of searchable fields
     *
     * @var array
     */
    protected $searchable = [
        'id',
        'name',
        'target_id',
        'target_type',
        'full_path',
        'added_by',
        'updated_by'
    ];

    protected $attributes = [
        'added_by' => 0,
        'updated_by' => 0
    ];
}
