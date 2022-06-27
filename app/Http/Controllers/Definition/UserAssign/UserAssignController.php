<?php

namespace App\Http\Controllers\Definition\UserAssign;

use App\Data\Models\Definition\UserAssign;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

class UserAssignController extends BaseController
{

    protected
        $user_assign, $meta_index = 'user_assign';

    function __construct(
        UserAssign $userAssign
    ){
        $this->user_assign = $userAssign;
        // middleware
    }

    protected $fillable = [
        'user_id',
        'approver_id',
    ];

    public function all()
    {

        $result = $this->user_assign->with('user')->with('approver')->get();

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved user assign.",
            "data"       => [
                $this->meta_index => $result,
            ]
        ]);

    }

    public function fetch( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;

        $result = $this->user_assign->where('id', $id)->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "No user assign are found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved user assign.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
                "parameters" => $data
            ]
        ]);

    }

    public function define( Request $request )
    {
        $data = $request->all();

        //region data validation
        foreach( $this->fillable as $value ){
            if(!isset($data[$value])){
                return response()->json([
                    'code' => 404,
                    'message' => $value. " is not set."
                ], 404);
            }
        }
        //endregion data validation

        //region existence check

        if (isset($data['id'])) {
            $does_exist = $this->user_assign->find($data['id']);

            if (!$does_exist) {
                return response()->json([
                    'code'  => 404,
                    'message' => 'User Assign ID does not exist.',
                ], 404);
            }
        }
        //endregion existence check

        $message = 'Successfully updated user assign.';

        //region insertion
        if (isset($data['id'])) {
            $model = $this->user_assign->find($data['id']);

        } else {
            $model  = $this->user_assign
                ->where('user_id', $data['user_id'])
                ->first();
            if( !$model ){
                $message = 'Successfully created user assign.';
                $model = $this->makeModel ( $data, $this->user_assign );
            }
        }

        if(!$model->save($data)){
            return response()->json([
                "code" => 500,
                "message" => "Data Validation Error.",
                "description" => "An error was detected on one of the inputted data.",
                "data" =>   [
                    "errors" => $model->errors(),
                ]
            ], 500);
        }

        return response()->json([
            "code" => 200,
            "message" => $message,
            "data" => [
                $this->meta_index => $model,
            ]
        ]);
        //endregion insertion

    }

    public function delete( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;
        $record = $this->meta->find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No user assign found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This user assign does not exists",
                "parameters" => $data
            ], 404);
        }

        if( $deleted_already->trashed() ){
            return response()->json([
                "code" => 200,
                "message" => "This id deleted already.",
                "parameters" => $data
            ]);
        }

        if(!$record->delete()){
            return response()->json([
                "code" => 500,
                "message" => "Deleting user assign was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "User Assign was successfully deleted,",
            "parameters" => $data
        ]);

    }

}
