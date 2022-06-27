<?php

namespace App\Http\Controllers\FlowControl\Definition;

use App\Data\Models\Definition\Approver;
use App\Data\Models\Employee\Employee;
use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;


/**
 * Class ApproverController
 * @package App\Http\Controllers\Definition
 */
class ApproverController extends BaseController
{
    protected $approver, $entity, $meta_index = 'approver';

    public function __construct(
        Approver $approver
    ){
        $this->approver = $approver;
    }

    protected $fillable = [
        'employee_id',
        'approver_unique_id',
        'name',
        'email',
        'type',
        'status',
    ];

    protected $column = [
        'id',
        'employee_id',
        'approver_unique_id',
        'name',
        'email',
        'type',
        'description',
        'status',
    ];

    public function all( Request $request )
    {

        $result = $this->approver->with('assign')->orderBy('id', 'DESC')->get();

        foreach($result as $value){
            $value['is_user'] = false;
            if( $request->user() !== null
                && $value['assign'] !== null
                && isset($value['assign']['user_id']) ){
                $id = $request->user()->id;
                if($value['assign']['user_id'] === $id){
                    $value['is_user'] = true;
                }
            }
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved flow control approver.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
            ]
        ]);

    }

    // region Define

    public function define( Request $request )
    {
        $data = $request->all();

        if( isset( $data['id'] ) ){
            if( !is_numeric( $data['id'] ) || $data['id'] <= 0 ){
                return response()->json([
                    'message' => "Invalid Approver ID.",
                ],  500);
            }
        }

        if( !isset( $data['approver_unique_id'] ) ){
            $data['approver_unique_id'] = sessioned_hash($data['employee_id']);
        }

        if( !isset( $data['status'] ) ){
            $data['status'] = 'active';
        }

        foreach($this->fillable as $newarr){
            if(!isset($data[$newarr])){
                return response()->json([
                    'code' => 404,
                    'message' => $newarr." is not set."
                ], 404);
            }
        }


        if( !is_array($data['type'])
            || count($data['type']) <= 0 ){
            return response()->json([
                'message' => "Invalid Type.",
            ],  500);
        }

        $data['type'] = implode(", ", $data['type']);

        $message = 'Successfully created approver.';
        if( isset( $data['id'] ) ){
            $model  = $this->approver->find($data['id']);

            if( !$model){
                return response()->json([
                    'message' => "This approver does not exists",
                ], 404);
            }
            $message = 'Successfully update approver.';
        } else {
            $model  = $this->approver
                ->where('email', $data['email'])
                ->first();
            if( !$model ){
                $model = $this->makeModel ( $data, $this->approver );
            }
        }

        if( !$model->save( $data ) ){
            $error_message = $model->errors();

            return response()->json([
                'message' => "Approver definition was not successful.",
                'data' => [
                    'errors' => $error_message,
                ]
            ], 404);
        }

        return response()->json([
            "code" => 200,
            'message' => $message,
            'data' => [
                $this->meta_index => $model,
            ]
        ]);

    }

    // endregion Define

    // region Delete

    public function delete( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;
        $record = $this->approver->find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No approver found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This approver does not exists",
                "parameters" => $data
            ], 404);
        }

        if( $deleted_already->trashed() ){
            return response()->json([
                "code" => 500,
                "message" => "This id deleted already.",
                "parameters" => $data
            ]);
        }

        if(!$record->delete()){
            return response()->json([
                "code" => 500,
                "message" => "Deleting approver was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Approver was successfully deleted,",
            "parameters" => $data
        ]);

    }

    // endregion Delete

    // region Retrieve Data

    public function fetch( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;

        $result = $this->approver->where('id', $id)->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "Approver not found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved approver.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
                "parameters" => $data
            ]
        ]);

    }

    // endregion Retrieve Data

    // region Retrieve Data

    public function fetchByEmail( Request $request )
    {
        $data = $request->all();

        if( !isset( $data['email'] ) ){
            return response()->json([
                'message' => "Email is not set.",
            ],  500);
        }

        $result = $this->approver->where('email', $data['email'])->first();

        if (!$result) {
            return response()->json([
                "code"       => 404,
                "message"      => "Approver not found",
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved approver.",
            "data"       => [
                $this->meta_index => $result,
                "parameters" => $data
            ]
        ]);

    }

    // endregion Retrieve Data


}
