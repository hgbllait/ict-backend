<?php

namespace App\Http\Controllers\FlowControl\Request;

use App\Http\Controllers\Notification\NotificationController;
use DB;
use App\Data\Models\Definition\Approver;
use App\Data\Models\Request\FlowControlRequest;
use App\Data\Models\Request\FlowControlRequestApprover;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Utilities\Meta\MetaController;
use Illuminate\Http\Request;


/**
 * Class FlowControlRequestApproverController
 * @package App\Http\Controllers\Request
 */
class FlowControlRequestApproverController extends BaseController
{
    protected $entity, $flow_control_request, $flow_control_request_approver, $notification_controller, $meta_controller;

    public function __construct(
        FlowControlRequest $flowControlRequest,
        FlowControlRequestApprover $flowControlRequestApprover,
        NotificationController $notificationController,
        MetaController $metaController
    ){
        $this->flow_control_request = $flowControlRequest;
        $this->flow_control_request_approver = $flowControlRequestApprover;
        $this->notification_controller = $notificationController;
        $this->meta_controller = $metaController;
    }

    protected $meta_index = 'flow_control_request_approver';

    protected $fillable = [
        'name',
        'approver_id',
        'flow_control_request_id',
        'override_reject',
        'override_accept',
        'required',
    ];

    protected $column =[
        'id',
        'name',
        'approver_id',
        'flow_control_request_id',
        'override_reject',
        'override_accept',
        'required',
    ];

    public function all( Request $request, $flow_control_request_id )
    {

        $data = $request->all();
        $data['flow_control_request_id'] = $flow_control_request_id;

        $result = $this->flow_control_request_approver->where('flow_control_request_id', $data['flow_control_request_id'])->get();

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

    public function define( Request $request, $flow_control_request_id )
    {
        $data = $request->all();
        $data['flow_control_request_id'] = $flow_control_request_id;

        if( isset( $data['id'] ) ){
            if( !is_numeric( $data['id'] ) || $data['id'] <= 0 ){
                return response()->json([
                    'message' => "Invalid flow control request approver ID.",
                ], 500);
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

        if (!is_numeric($data['approver_id']) || $data['approver_id'] <= 0) {
            return response()->json([
                'code' => 500,
                'message' => "Invalid Approver ID.",
            ], 500);
        }

        $approver = new Approver();
        $approver = $approver->find($data['approver_id']);

        if (!$approver) {
            return response()->json([
                'code'  => 404,
                'message' => 'Approver ID does not exist.',
            ], 404);
        }

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

        $message = 'Successfully created flow control request approver.';
        if( isset( $data['id'] ) ){
            $model  = $this->flow_control_request_approver->find($data['id']);
            if( !$model){
                return response()->json([
                    'message' => "This flow control request approver does not exists",
                ], 404);
            }
            $message = 'Successfully update flow control request approver.';
        } else {
            $model  = $this->flow_control_request_approver
                ->where('approver_id', $data['approver_id'])
                ->where('flow_control_request_id', $data['flow_control_request_id'])
                ->first();
            if( !$model ){
                $model = $this->makeModel ( $data, $this->flow_control_request_approver );
            }
        }

        if(isset($data['approvestatus'])
            && $data['approvestatus'] == true){
            $data['approval_status'] = 'approved';
        }

        if(isset($data['approveoverride'])
            && $data['approveoverride'] == true){
            $data['override_accept'] = 'true';
        }

        DB::beginTransaction();
        try {

            if( !$model->save($data) ){
                $error_message = $model->errors();

                return response()->json([
                    'message' => "Flow control request approver was not successful.",
                    'data' => [
                        'errors' => $error_message,
                    ]
                ], 500);
            }

            $link = '';
            $password = '';
            $meta_definition = $this->meta_controller->define( new Request([
                'target_type' => 'flow_control_request',
                'target_id' => $data['flow_control_request_id'],
                'meta_key' => $data['name'] . "_name",
                'meta_value' => $approver->name,
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

            if( (isset($data['approveoverride'])
                && $data['approveoverride'] == true)
                ||
                (isset($data['approvestatus'])
                    && $data['approvestatus'] == true)){
                $meta_definition = $this->meta_controller->define( new Request([
                    'target_type' => 'flow_control_request',
                    'target_id' => $data['flow_control_request_id'],
                    'meta_key' => $data['name'] . "_date_signed",
                    'meta_value' => date('Y-m-d'),
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

            foreach (['link', 'password'] as $value) {
                if($value === 'link') {
                    do {
                        $meta_value = generate_random_string(20);
                        $link = $meta_value;
                        $meta_fetch = $this->meta_controller->fetchByTarget( new Request([
                            'target_type' => 'request_approver',
                            'target_id' => $model->id,
                            'meta_key' => $value,
                            'meta_value' => $meta_value
                        ]));
                    } while (
                        !isset($meta_fetch)
                        && !is_code_success( $meta_fetch->status() )
                    );

                }
                else {
                    $meta_value = generate_random_string(5);
                    $password = $meta_value;
                }
                $meta_definition = $this->meta_controller->define( new Request([
                    'target_type' => 'request_approver',
                    'target_id' => $model->id,
                    'meta_key' => $value,
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

            $approver = $this->flow_control_request_approver->where('id', $model->id)->with('approver')->first();

            $flow_control_request = $this->flow_control_request->where('id', $data['flow_control_request_id'])->with('forms')->first();

            if( isset($data['notify'])
                && $data['notify']){
                $message = $flow_control_request['forms']['name']. ' form added you as a signatory.';
                $notification = $this->notification_controller->sendLinkPassword($message,
                    $flow_control_request['forms']['name'],
                    $approver['approver']['name'],
                    $link,
                    $password,
                    $approver['approver']['email']);

                if(isset($notification) && !is_code_success( $notification->status() ) ){
                    DB::rollback();
                    return response()->json([
                        "code" => 500,
                        "message" => "Failed to send a notification."
                    ], 500);
                }
            }

            DB::commit();
            return response()->json([
                'message' => $message,
                'data' => [
                    'flow_control_request_approver' => $model,
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
        $record = $this->flow_control_request_approver->find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No Flow control approver found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This Flow control approver does not exists",
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
                "message" => "Deleting flow control approver was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Flow control approver was successfully deleted,",
            "parameters" => $data
        ]);

    }

    // endregion Delete

    // region Retrieve Data

    public function fetch( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;

        $result = $this->flow_control_request_approver->where('id', $id);

        if(isset($data['relationship'])) {
            $result = $result->with($data['relationship']);
        }

        if(isset($data['single']) && $data['single']){
            $result = $result->first();
            $count = 1;
            if(!$result) $count = 0;
        } else {
            $result = $result->get();
            $count = $result->count();
        }

        if ($count < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "Flow control approver not found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved flow control approver.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $count,
                "parameters" => $data
            ]
        ]);

    }

    // endregion Retrieve Data

    public function fetchRequestApproversStatus( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;


        $approver = $this->flow_control_request_approver
            ->with('approver')
            ->where('flow_control_request_id', $id)->get();

        $approved = $this->flow_control_request_approver
            ->where('flow_control_request_id', $id)
            ->where('approval_status', 'approved')->get();

        $rejected = $this->flow_control_request_approver
            ->where('flow_control_request_id', $id)
            ->where('approval_status', 'rejected')->get();

        $pending = $this->flow_control_request_approver
            ->where('flow_control_request_id', $id)
            ->where('approval_status', '!=', 'approved')
            ->where('approval_status', '!=', 'rejected')->get();

        if($approver->count() <= 0) $percentage = 100;
        else $percentage = number_format((float)percentage(($approved->count() + $rejected->count()),$approver->count() ), 2, '.', '');

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved request approvers.",
            "data"       => [
                "approver"     => $approver,
                "percentage"     => $percentage,
                'all' => $approver->count(),
                "pending" => $pending->count(),
                "approved"     => $approved->count(),
                "rejected"     => $rejected->count(),
                "parameters" => $data
            ]
        ]);
    }

    public function updateApprovalStatus( $data=[] )
    {

        $status = FlowControlRequestApprover::select('approval_status')
            ->where('id', $data['id'])
            ->first()->approval_status;

        $updated_already =  FlowControlRequestApprover::where( 'id', "=", $data['id'] )
            ->update(['approval_status' => $data['approval_status']]);

        return $data;
    }

}
