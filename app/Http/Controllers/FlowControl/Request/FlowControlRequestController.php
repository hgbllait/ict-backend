<?php

namespace App\Http\Controllers\FlowControl\Request;

use App\Data\Models\Definition\Approver;
use App\Data\Models\Definition\Rule;
use App\Data\Models\Request\FlowControlRequest;
use App\Data\Models\Request\FlowControlRequestLog;
use App\Data\Models\Utilities\Files\File;
use App\Data\Models\Utilities\Meta\Meta;
use App\Helpers\FlowControlHelper;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\FlowControl\Definition\ApproverController;
use App\Http\Controllers\FlowControl\FlowControlController;
use App\Data\Models\Request\FlowControlRequestApprover;
use App\Http\Controllers\Utilities\Meta\MetaController;
use Illuminate\Http\Request;
use DB;


/**
 * Class FlowControlRequestController
 * @package App\Http\Controllers\Request
 */
class FlowControlRequestController extends BaseController
{
    protected  $entity,
        $approver_controller,
        $flow_control_ctrl,
        $flow_control_request_approver,
        $flow_control_request_log,
        $flow_control_request, $helper,
        $flow_control_request_rule, $rule,
        $meta_controller,
        $meta_index = 'flow_control_request';

    public function __construct(
        FlowControlRequest $flowControlRequest,
        FlowControlController $flowControlRequestController,
        FlowControlRequestApproverController $flowControlRequestApproverController,
        FlowControlRequestLog $flowControlRequestLog,
        FlowControlHelper $helper,
        ApproverController $approverController,
        MetaController $metaController,
        Rule $rule
    ){
        $this->flow_control_ctrl = $flowControlRequestController;
        $this->flow_control_request = $flowControlRequest;
        $this->flow_control_request_approver = $flowControlRequestApproverController;
        $this->flow_control_request_log = $flowControlRequestLog;
        $this->meta_controller = $metaController;
        $this->approver_controller = $approverController;
        $this->rule = $rule;
        $this->helper = $helper;
    }

    protected $fillable = [
        'form_id',
        'name',
        'approval_status',
    ];

    protected $column =[
        'id',
        'form_id',
        'name',
        'approval_status',
    ];

    protected $rule_meta = [
        'priority',
        'percentage',
    ];

    protected $fillable_rule_request = [
        'name',
        'description',
        'status',
    ];

    public function all( Request $request )
    {
        $data = $request->all();
        $result = $this->flow_control_request;

        if(isset($data['relationship'])) {
            $result = $this->flow_control_request->with($data['relationship']);
        }

        $result = $result->orderBy('id', 'DESC')->get();

        if(isset($data['status'])) {
            foreach($result as $value){
                $value['status'] = $this->flow_control_request_approver->fetchRequestApproversStatus(new Request(), $value->id)->getData()->data;
                if(isset($data['link'])) {
                    foreach($value['status']->approver as $value_){
                        $link = Meta::where('meta_key', 'link')
                            ->where('target_type', '=', 'request_approver')
                            ->where('target_id', '=', $value_->id)->pluck('meta_value')->first();
                        if($link){
                            $value_->_link = $link;
                        }
                    }
                }
            }
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved flow control request.",
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
                    'message' => "Invalid flow control request ID.",
                ]);
            }
        }

        if( !isset($data['approval_status']) ){
            $data['approval_status'] = "false";
        }

        foreach($this->fillable as $newarr){
            if(!isset($data[$newarr])){
                return response()->json([
                    'code' => 404,
                    'message' => $newarr." is not set."
                ], 404);
            }
        }

        $flow_control_request = $this->makeModel ( $data, $this->flow_control_request );

        $message = 'Successfully created flow control request.';
        if( isset( $data['id'] ) ){
            $flow_control_request  = $this->flow_control_request->find($data['id']);
            if( !$flow_control_request){
                return response()->json([
                    'message' => "This flow control request does not exists",
                ], 404);
            }
            $message = 'Successfully update flow control request.';
        }

        DB::beginTransaction();
        try {
            if (!$flow_control_request->save($data)) {
                $error_message = $flow_control_request->errors();
                return response()->json([
                    'message' => "Flow control request was not successful.",
                    'data' => [
                        'errors' => $error_message,
                    ]
                ], 500);
            }

            $flow_log = new FlowControlRequestLog();
            $flow_log->save([
                'flow_control_request_id' => $flow_control_request->id,
                'event_description' => 'New request appeared.'
            ]);

            if (isset($data['flow_control_request_approver'])) {
                foreach ($data['flow_control_request_approver'] as $key => $value) {
                    if(!isset($value["approver_id"])){
                        $approver_definition = $this->approver_controller->define( new Request([
                            'email' => $value['email'],
                            'name' => $value['name'],
                            'employee_id' => $value['employee_id'] ?? 0,
                            'type' => 'End User',
                            'added_by' => $value['added_by'] ?? 0,
                            'fresh' => true
                        ]));

                        if( isset($approver_definition)
                            && !is_code_success( $approver_definition->status() ) ){
                            DB::rollback();
                            return response()->json([
                                "code" => 500,
                                "message" => $approver_definition->getData()->message
                            ], 500);
                        }
                        $value['name'] = 'requested';
                        $value["approver_id"] = $approver_definition->getData()->data->approver->id;

                    }

                    if (!Approver::find($value["approver_id"])) {
                        DB::rollback();
                        return response()->json([
                            'code' => 404,
                            'message' => "Approver does not exist.",
                        ], 404);
                    }

                    if(!isset($value['name'])){
                        DB::rollback();
                        return response()->json([
                            'code' => 404,
                            'message' => "Name is not set.",
                        ], 404);
                    }

                    $required = false;
                    if(isset($value['required'])){
                        $required = $value['required'];
                    }
                    $notify = false;
                    if(isset($value['notify'])){
                        $notify = $value['notify'];
                    }
                    $approvestatus = false;
                    if(isset($value['approvestatus'])){
                        $approvestatus = $value['approvestatus'];
                    }
                    $approveoverride = false;
                    if(isset($value['approveoverride'])){
                        $approveoverride = $value['approveoverride'];
                    }

                    $flow_control_request_approver = $this->flow_control_request_approver->define(new Request([
                        'approver_id' => $value['approver_id'],
                        'name' => $value['name'],
                        'flow_control_request_id' => $flow_control_request->id,
                        'approval_status' => 'false',
                        'override_reject' => 'false',
                        'override_accept' => 'false',
                        'required' => $required,
                        'notify' => $notify,
                        'approvestatus' => $approvestatus,
                        'approveoverride' => $approveoverride,
                        'added_by' => $data['added_by'] ?? 0,
                        'fresh' => true,
                    ]), $flow_control_request->id);

                    if (!is_code_success($flow_control_request_approver->status())) {
                        DB::rollback();
                        return response()->json([
                            'code' => 500,
                            'message' => $flow_control_request_approver->getData(),
                        ], 500);
                    }
                }
                $result = $this->enhancedBuild($flow_control_request->id);

                if(isset($result) && !is_code_success( $result->status() ) ){
                    DB::rollback();
                    return response()->json([
                        "code" => 500,
                        "data" => $result->getData()
                    ], 500);
                }

            }

            DB::commit();
            return response()->json([
                'message' => $message,
                'data' => [
                    $this->meta_index => $flow_control_request,
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage()
            ], 200);
        }

    }

    // endregion Define

    // region Delete

    public function delete( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;
        $record = $this->flow_control_request->find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No Flow control request found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This Flow control request does not exists",
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
                "message" => "Deleting flow control request was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Flow control request was successfully deleted,",
            "parameters" => $data
        ]);

    }

    // endregion Delete

    // region Retrieve Data

    public function fetch( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;

        $result = $this->flow_control_request->where('id', $id)->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "Flow control request not found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved flow control request.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
                "parameters" => $data
            ]
        ]);
    }

    // endregion Retrieve Data

    // region Verify Form

    public function verify( Request $request )
    {
        $data = $request->all();

        foreach(['link', 'password'] as $value){
            if(!isset($data[$value])){
                return response()->json([
                    'code' => 404,
                    'message' => $value." is not set."
                ], 404);
            }
        }

        $link = $this->meta_controller->fetchByTarget(new Request([
            'meta_key' => 'link',
            'meta_value' => $data['link'],
            'target_type' => 'request_approver',
            'single' => true
        ]));

        if( isset($link)
            && !is_code_success( $link->status() ) ){
            return response()->json([
                "code"       => 500,
                "message"      => "Invalid link.",
            ], 500);
        }
        $link = $link->getData()->data->metas->target_id;

        $password = $this->meta_controller->fetchByTarget(new Request([
            'meta_key' => 'password',
            'meta_value' => $data['password'],
            'target_type' => 'request_approver',
            'target_id' => $link,
            'single' => true
        ]));

        if( isset($password)
            && !is_code_success( $password->status() ) ){
            return response()->json([
                "code"       => 500,
                "message"      => "Password is incorrect.",
            ], 500);
        }

        $password = $password->getData()->data->metas->target_id;

        if($password !== $link){
            return response()->json([
                "code"       => 500,
                "message"      => "Invalid password for this form.",
            ], 500);
        }

        $flow_control_request_approver = $this->flow_control_request_approver
            ->fetch(new Request(['relationship' => ['approver','flowControl', 'user'], 'single' => true]), $link);
        if(isset($flow_control_request_approver) && !is_code_success( $flow_control_request_approver->status() ) ){
            return response()->json([
                "code" => 500,
                "message" => $flow_control_request_approver->getData()->message,
            ], 500);
        }
        $flow_control_request_approver = $flow_control_request_approver->getData()->data->flow_control_request_approver;

        $signature = File::where('target_type', 'approver')
            ->where('name', 'approver_signature')
            ->where('target_id', $flow_control_request_approver->approver_id)->first();

        if($signature && isset($signature->toArray()['full_path'])){
            $response = \Storage::get($signature->toArray()['full_path']);
            $array = explode('.', $signature['full_path']);
            $extensions = end($array);
            $signature = 'data:image/'.  $extensions . ';base64,' . base64_encode($response);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully verify request.",
            "data" => [
                "flow_control_request_approver" => $flow_control_request_approver,
                "signature" => $signature

            ]
        ]);
    }

    // endregion Verify Form

    // region Verify Form

    public function formApproval( Request $request )
    {
        $data = $request->all();

        foreach(['action', 'flow_control_request_id', 'flow_control_request_approver_id'] as $value){
            if(!isset($data[$value])){
                return response()->json([
                    'code' => 404,
                    'message' => $value." is not set."
                ], 404);
            }
        }

        $remarks = [];
        if(isset($data['remarks'])){
            $remarks = [
                'remarks' => $data['remarks']
            ];
        }

        if($data['action']){
            $message = 'Form request approved successful.';
            $approval_status = $this->approve( new Request($remarks), $data['flow_control_request_id'], $data['flow_control_request_approver_id'] );
        } else {
            $message = 'Form request rejected successful.';
            $approval_status = $this->reject( new Request($remarks), $data['flow_control_request_id'], $data['flow_control_request_approver_id'] );
        }

        if( isset($approval_status)
            && !is_code_success( $approval_status->status() ) ){
            DB::rollback();
            return response()->json($approval_status->getData(), 500);
        }

        // Insert Log
        return response()->json([
            "code"       => 200,
            "message"      => $message
        ]);
    }

    // endregion Verify Form

    // region Functions

    public function approve( Request $request, $flow_control_request_id, $id )
    {
        $data = $request->all();
        $data = array_merge([
            'id' => $id,
            'flow_control_request_id' => $flow_control_request_id,
            "approval_status" => "approved"
        ], $data);
        return $this->updateApprovalStatus( $data );

    }

    public function reject( Request $request, $flow_control_request_id, $id  )
    {
        $data = $request->all();
        $data = array_merge([
            'id' => $id,
            'flow_control_request_id' => $flow_control_request_id,
            "approval_status" => "rejected"
        ], $data);
        return $this->updateApprovalStatus( $data );
    }

    protected function updateApprovalStatus( $data )
    {
        foreach(['id', 'flow_control_request_id', 'approval_status'] as $newarr){
            if(!isset($data[$newarr])){
                return response()->json([
                    'code' => 404,
                    'message' => $newarr." is not set."
                ], 404);
            }
        }

        $approver = FlowControlRequestApprover::with('approver')
            ->where('id', $data['id'])->first();
        $approver = $approver->toArray();
        if(isset($data['override'])
            && $data['override']){
            $approve = 'rejected';
            if($data['approval_status'] === 'rejected'){
                FlowControlRequestApprover::where( 'flow_control_request_id', "=", $data['flow_control_request_id'] )
                    ->where( 'id', "=", $data['id'] )
                    ->update(['override_reject' => "true", 'override_accept' => "false", 'approval_status' => $data['approval_status']]);
            } else {
                $approve = 'approved';
                FlowControlRequestApprover::where( 'flow_control_request_id', "=", $data['flow_control_request_id'] )
                    ->where( 'id', "=", $data['id'] )
                    ->update(['override_accept' => "true", 'override_reject' => "false", 'approval_status' => $data['approval_status']]);
            }
            $this->flow_control_request_log->save([
                'flow_control_request_id' => $data['flow_control_request_id'],
                'event_description' => $approver['approver']['name'].' override '.$approve.' a request.'
            ]);
        } else {
            FlowControlRequestApprover::where( 'flow_control_request_id', "=", $data['flow_control_request_id'] )
                ->where( 'id', "=", $data['id'] )
                ->update([
                    'approval_status' => $data['approval_status'],
                    'override_accept' => "false",
                    'override_reject' => "false"]);
            $this->flow_control_request_log->save([
                'flow_control_request_id' => $data['flow_control_request_id'],
                'event_description' => $approver['approver']['name']. ' '. $data['approval_status'].' a request. '
            ]);


        }
        $id = $data['flow_control_request_id'];

        $record = File::where('name', 'approver_signature')
            ->where('target_type', 'approver')
            ->where('target_id', $approver['approver_id'])->first();

        DB::beginTransaction();
        try {
            $data['action'] = 0;
            if($data['approval_status'] == 'approved'){
                $data['action'] = 1;
            }

            // Insert Meta
            foreach(['date_signed', 'remarks', 'signature'] as $value){
                if( $value === 'remarks' && isset($data['remarks']) ){
                    $meta_value = $data['remarks'];
                }
                else if($value === 'date_signed') {
                    if( $data['action'] ){
                        $meta_value = date('Y-m-d');
                    } else {
                        $meta_deletion = Meta::where('target_type', 'flow_control_request')
                            ->where('target_id', $id)
                            ->where('meta_key', $approver['name'].'_date_signed');
                        if($meta_deletion){
                            $meta_deletion->forceDelete();
                        }
                        continue;
                    }
                } else if($value === 'signature') {
                    if( $data['action'] && $record ){
                        $meta_value = $record->full_path;
                    } else {
                        $meta_deletion = Meta::where('target_type', 'flow_control_request')
                            ->where('target_id', $id)
                            ->where('meta_key', $approver['name'].'_signature');
                        if($meta_deletion){
                            $meta_deletion->forceDelete();
                        }
                        continue;
                    }
                } else {
                    if(!isset($data['remarks'])){
                        $meta_deletion = Meta::where('target_type', 'flow_control_request')
                            ->where('target_id', $id)
                            ->where('meta_key', $approver['name'].'_remarks');
                        if($meta_deletion){
                            $meta_deletion->forceDelete();
                        }
                    }
                    continue;
                }

                $meta_definition = $this->meta_controller->define( new Request([
                    'target_type' => 'flow_control_request',
                    'target_id' =>  $id,
                    'meta_key' => $approver['name'].'_'.$value,
                    'meta_value' => $meta_value,
                    'added_by' => $data['added_by'] ?? 0,
                    'fresh' => true
                ]));

                if( isset($meta_definition)
                    && !is_code_success( $meta_definition->status() ) ){
                    DB::rollback();
                    return response()->json([
                        "code" => 500,
                        "message" => $meta_definition->getData()->message
                    ], 500);
                }

            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'code'  => 500,
                'message' => 'Something went wrong.',
                'description' => $e->getMessage()
            ], 500);

        }

        $result = $this->enhancedBuild($id);

        if(isset($result) && !is_code_success( $result->status() ) ){
            return response()->json([
                "code" => 500,
                "data" => $result->getData()
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully generate flow control request.",
            "data"       => [
                $this->meta_index => $result->getData()->data->result,
                'total_approved_requests' => $result->getData()->data->approvers->total_approved_requests,
                "parameters" => $data
            ]
        ], 200);

    }

    protected function buildParamsByFlowControlRequests( $data=[] )
    {

        if( isset( $data['id']) ){
            if( !is_numeric( $data['id'] ) || $data['id']<= 0 ){
                return response()->json([
                    'message' => "Invalid ID.",
                ]);
            }
        }

        $result = $this->flow_control_request->find( $data['id'] );

        // START RULE BY FLOW CONTROL REQUEST ID
        $get_rule_by_flow_control_request = $this->rule
            ->select('rules.id', 'rules.title')
            ->join('flow_control_request_rules', 'rules.id', 'flow_control_request_rules.rule_id')
            ->where('flow_control_request_id', $data['id'])
            ->get()->toArray();
        // END RULE BY FLOW CONTROL REQUEST ID

        // END PERCENTAGE BY FLOW CONTROL REQUEST ID
        // START TOTAL APPROVER BY FLOW CONTROL REQUEST ID
        $query_total_approver = FlowControlRequestApprover::select('*')
            ->where('flow_control_request_id', '=', $data['id']);

        $fcr_approver_by_fcr_id = $query_total_approver->get()->toArray();
        // END TOTAL APPROVER BY FLOW CONTROL REQUEST ID

        // START TOTAL APPROVED FLOW CONTROL REQUEST BY FLOW CONTROL REQUEST ID
        $query_total_approved = FlowControlRequestApprover::select('*')
            ->where('flow_control_request_id', '=', $data['id']);
        $count_total_all_by_fcr_id = $query_total_approved->count();
        $count_total_approved_by_fcr_id = $query_total_approved
            ->where('approval_status', '=', 'approved')->count();
        // END TOTAL APPROVED FLOW CONTROL REQUEST BY FLOW CONTROL REQUEST ID

        if( !$result ){
            return response()->json([
                'message' => "This flow control request does not exists",
            ], 404);
        }

        if( isset( $data['is_model'] ) && $data['is_model'] === true ){
            return $result;
        }

        return response()->json([
            'message' => "Successfully retrieved data per flow control request",
            'data' => [
                'flow_control_request_id' => $data['id'],
                'rules' => $get_rule_by_flow_control_request,
                'approvers' => $fcr_approver_by_fcr_id,
                'total_approved_requests' => $count_total_approved_by_fcr_id,
                'total_request' => $count_total_all_by_fcr_id
            ]
        ]);
    }

    public function enhancedBuild($id){
        $data = [
            "approval_status" => false,
            "update_request" => false,
            "status" => false,
            "id" => $id
        ];

        $builders = $this->buildParamsByFlowControlRequests([
            'id' => $id,
            'as_array' => true,
        ] );

        if( !is_code_success($builders->getStatusCode()) ){
            return response()->json([
                'message' => "Failed to update flow control request",
            ], 404);
        }

        $approvers = json_decode(json_encode(
            $builders->getData()->data
        ), true);

        $params = array_merge(
            [
                "approvers" => $approvers['approvers']
            ]
        );

        $result = $this->flow_control_ctrl->generate( $data, $params );
        if( $result['update_request'] == true){
            FlowControlRequest::where( 'id', "=", $id )
                ->update(['approval_status' => $result['status'] ] );
            $flow_log = new FlowControlRequestLog();
            $flow_log->save([
                'flow_control_request_id' => $id,
                'event_description' => 'Request ID: '.$id.' Completed: '. ucfirst($result['status'])
            ]);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully generate flow control request.",
            "data"       => [
                'result' => $result,
                'approvers' => $approvers
            ]
        ]);

    }

    // endregion Functions

}
