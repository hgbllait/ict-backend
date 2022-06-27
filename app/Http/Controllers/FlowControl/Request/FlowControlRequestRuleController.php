<?php

namespace App\Http\Controllers\FlowControl\Request;

use App\Data\Models\Definition\Rule;
use App\Data\Models\Request\FlowControlRequest;
use App\Data\Models\Request\FlowControlRequestRule;
use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;

/**
 * Class FlowControlRequestRuleController
 * @package App\Http\Controllers\Request
 */
class FlowControlRequestRuleController extends BaseController
{
    protected $flow_control_request,
        $flow_control_request_rule,
        $meta_index = 'flow_control_request_rule';

    public function __construct(
        FlowControlRequest $flowControlRequest,
        FlowControlRequestRule $flow_control_request_rule
    ){
        $this->flow_control_request = $flowControlRequest;
        $this->flow_control_request_rule = $flow_control_request_rule;
    }

    protected $fillable = [
        'name',
        'flow_control_request_id',
        'rule_id',
    ];

    protected $column =[
        'id',
        'name',
        'flow_control_request_id',
        'rule_id',
    ];

    public function all( Request $request, $flow_control_request_id )
    {

        $data = $request->all();
        $data['flow_control_request_id'] = $flow_control_request_id;

        $result = $this->flow_control_request_rule->where('flow_control_request_id', $data['flow_control_request_id'])->get();

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved flow control rule.",
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
                    'message' => "Invalid flow control request rule ID.",
                ], 500);
            }
        }

        if ( isset( $data['rule_id'] ) ) {
            if (!is_numeric($data['rule_id']) || $data['rule_id'] <= 0) {
                return response()->json([
                    'code' => 500,
                    'message' => "Invalid Rule ID.",
                ], 500);
            }

            if (isset($data['id'])) {
                $rule = new Rule();
                $does_exist = $rule->find($data['id']);

                if (!$does_exist) {
                    return response()->json([
                        'code'  => 404,
                        'message' => 'Rule ID does not exist.',
                    ], 404);
                }
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

        $message = 'Successfully created flow control request rule.';
        if( isset( $data['id'] ) ){
            $model  = $this->flow_control_request_rule->find($data['id']);
            if( !$model){
                return response()->json([
                    'message' => "This flow control request rule does not exists",
                ], 404);
            }
            $message = 'Successfully update flow control request rule.';
        } else {
            $model  = $this->flow_control_request_rule
                ->where('rule_id', $data['rule_id'])
                ->where('flow_control_request_id', $data['flow_control_request_id'])
                ->first();
            if( !$model ){
                $model = $this->makeModel ( $data, $this->flow_control_request_rule );
            }
        }

        if( !$model->save($data) ){
            $error_message = $model->errors();

            return response()->json([
                'message' => "Flow control request rule was not successful.",
                'data' => [
                    'errors' => $error_message,
                ]
            ], 500);
        }

        return response()->json([
            'message' => $message,
            'data' => [
                'flow_control_request_rule' => $model,
            ]
        ], 200);

    }

    // endregion Define

    // region Delete

    public function delete( Request $request, $id )
    {

        $data = $request->all();
        $data['id'] = $id;
        $record = $this->flow_control_request_rule->find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No Flow control rule found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This Flow control rule does not exists",
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
                "message" => "Deleting flow control rule was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Flow control rule was successfully deleted,",
            "parameters" => $data
        ]);

    }

    // endregion Delete

    // region Retrieve Data

    public function fetch( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;

        $result = $this->flow_control_request_rule->where('id', $id)->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "Flow control rule not found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved flow control rule.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
                "parameters" => $data
            ]
        ]);

    }

    // endregion Retrieve Data
}
