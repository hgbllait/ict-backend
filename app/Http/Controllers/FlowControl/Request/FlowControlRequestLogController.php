<?php

namespace App\Http\Controllers\FlowControl\Request;

use App\Data\Models\Request\FlowControlRequest;
use App\Data\Models\Request\FlowControlRequestLog;
use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;


/**
 * Class FlowControlRequestLogController
 * @package App\Http\Controllers\Request
 */
class FlowControlRequestLogController extends BaseController
{
    protected $flow_control_request,
        $flow_control_request_log,
        $meta_index = 'flow_control_request_log';

    public function __construct(
        FlowControlRequest $flowControlRequest,
        FlowControlRequestLog $flow_control_request_log
    ){
        $this->flow_control_request = $flowControlRequest;
        $this->flow_control_request_log = $flow_control_request_log;
    }

    protected $fillable = [
        'flow_control_request_id',
        'event_description',
    ];

    protected $column =[
        'id',
        'flow_control_request_id',
        'event_description',
    ];

    public function all( Request $request, $flow_control_request_id )
    {

        $data = $request->all();
        $data['flow_control_request_id'] = $flow_control_request_id;

        $result = $this->flow_control_request_log->where('flow_control_request_id', $data['flow_control_request_id'])->get();

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved flow control log.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
            ]
        ]);

    }

    // region Define

    public function define( Request $request, $flow_control_request_id )
    {
        $data = $request->all();
        $data['flow_control_request_id'] = $flow_control_request_id;

        if( isset( $data['id'] ) ){
            if( !is_numeric( $data['id'] ) || $data['id'] <= 0 ){
                return response()->json([
                    'message' => "Invalid flow control request log ID.",
                ], 500);
            }
        }

        if(isset($data['flow_control_request_id'])){
            if (!is_numeric($data['flow_control_request_id']) || $data['flow_control_request_id'] <= 0) {
                return response()->json([
                    'code' => 500,
                    'message' => "Invalid Flow Control Request ID.",
                ], 500);
            }

            if ( !$this->flow_control_request->find( $data['flow_control_request_id'])  ) {
                return response()->json([
                    'code' => 404,
                    'message' => "Flow Control Request ID does not exist.",
                ], 404);
            }
        }

        foreach($this->fillable as $newarr){
            if(!isset($data[$newarr])){
                return response()->json([
                    'code' => 404,
                    'message' => $newarr." is not set."
                ], 404);
            }
        }

        $model = $this->makeModel ( $data, $this->flow_control_request_log );

        $message = 'Successfully created flow control request log.';
        if( isset( $data['id'] ) ){
            $model  = $this->flow_control_request_log->find($data['id']);
            if( !$model){
                return response()->json([
                    'message' => "This flow control request log does not exists",
                ], 404);
            }
            $message = 'Successfully update flow control request log.';
        }

        if( !$model->save($data) ){
            $error_message = $model->errors();

            return response()->json([
                'message' => "Flow control request log was not successful.",
                'data' => [
                    'errors' => $error_message,
                ]
            ], 500);
        }

        return response()->json([
            'message' => $message,
            'data' => [
                'flow_control_request_log' => $model,
            ]
        ], 200);

    }

    // endregion Define

    // region Delete

    public function delete( Request $request, $id )
    {

        $data = $request->all();
        $data['id'] = $id;
        $record = $this->flow_control_request_log->find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No Flow control log found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This Flow control log does not exists",
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
                "message" => "Deleting flow control log was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Flow control log was successfully deleted,",
            "parameters" => $data
        ]);

    }

    // endregion Delete

    // region Retrieve Data

    public function fetch( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;

        $result = $this->flow_control_request_log->where('id', $id)->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "Flow control log not found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved flow control log.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
                "parameters" => $data
            ]
        ]);

    }

    // endregion Retrieve Data

}
