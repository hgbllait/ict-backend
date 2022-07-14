<?php

namespace App\Http\Controllers\Definition\Form;

use App\Data\Models\Auth\User;
use App\Data\Models\Definition\Approver;
use App\Data\Models\Definition\JobOrderCount;
use App\Data\Models\Definition\UserAssign;
use App\Data\Models\Employee\Employee;
use App\Data\Models\Request\FlowControlRequest;
use App\Data\Models\Request\FlowControlRequestApprover;
use App\Data\Models\Utilities\Files\File;
use App\Data\Models\Utilities\Meta\Meta;
use App\Http\Controllers\FlowControl\Request\FlowControlRequestApproverController;
use DB;
use Illuminate\Http\Request;
use App\Data\Models\Definition\Form\Form;
use App\Data\Models\Definition\Form\FormType;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\FlowControl\Request\FlowControlRequestController;
use App\Http\Controllers\Utilities\File\FileController;
use App\Http\Controllers\Utilities\Meta\MetaController;

class FormController extends BaseController
{

    protected $meta_index = 'forms',
        $form,
        $form_type,
        $meta_controller,
        $file_controller,
        $flow_control_request_controller,
        $flow_control_request_approver_controller,
        $fillable = [
        'name',
        'revision_no',
        'issue_no',
        'date_effective',
        'form_type',
        'type_id',
    ];

    function __construct(
        Form $form,
        FormType $formType,
        MetaController $metaController,
        FileController $fileController,
        FlowControlRequestController $flowControlRequest,
        FlowControlRequestApproverController $flowControlRequestApproverController
    ){
        $this->form = $form;
        $this->form_type = $formType;
        $this->meta_controller = $metaController;
        $this->file_controller = $fileController;
        $this->flow_control_request_controller = $flowControlRequest;
        $this->flow_control_request_approver_controller = $flowControlRequestApproverController;
        // middleware
    }

    public function all( Request $request )
    {

        $data = $request->all();

        $result = $this->form;

        if(isset($data['relationship'])) {
            $result = $this->form->with($data['relationship']);
        }

        $result = $result->with('flowControl')->orderBy('id', 'DESC')->get();

        foreach($result as $value){
            $has_job_order = Meta::where('meta_key', 'job_order_no')
                ->where('target_type', '=', 'forms')
                ->where('target_id', '=',  $value['id'])->pluck('meta_value')->first();
            if(!$has_job_order
                && $value['type_id'] == 1
                && $value['flowControl']
                && $value['flowControl']['approval_status'] === 'approved') {
                $value['flowControl']['approval_status'] = 'processing';
            }
            if($value['added_by'] == 0){
                $value['added_by'] = [
                    'full_name' => 'System generated'
                ];
            } else {
                $user = User::with('employee')->where('id', $value['added_by'])->first();
                $value['added_by'] = $user['employee'];
            }
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved form.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
            ]
        ]);

    }

    public function fetch( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;

        $result = $this->form->where('id', $id);

        if(isset($data['relationship'])) {
            $result = $result->with($data['relationship']);
        }

        $result = $result->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "No form found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved form.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
                "parameters" => $data
            ]
        ]);

    }

    public function fetchType( Request $request )
    {
        $result = $this->form_type;

        $result = $result->get();
        $form_type = [];
        foreach( $result as $value ){
            if($value->id == 4 || $value->id == 9) continue;
            $form_type[] = $value;
        }
        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved form type.",
            "data"       => [
                'form_type' => $form_type,
                "count"     => count($form_type)
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

        $model = $this->makeModel ( $data, $this->form );

        //region existence check
        if (isset($data['type_id'])) {
            $does_exist = FormType::find($data['type_id']);

            if (!$does_exist) {
                return response()->json([
                    'code'  => 404,
                    'message' => 'Form Type ID does not exist.',
                ], 404);
            }
        }
        if (isset($data['id'])) {
            $does_exist = $this->form->find($data['id']);

            if (!$does_exist) {
                return response()->json([
                    'code'  => 404,
                    'message' => 'Form ID does not exist.',
                ], 404);
            }
        }
        //endregion existence check

        $message = 'Successfully created form.';

        //region insertion
        if (isset($data['id'])) {
            $model = $this->form->find($data['id']);
            $message = 'Successfully updated form.';

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
        $record = $this->form->find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No form found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This form does not exists",
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
                "message" => "Deleting form was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Form was successfully deleted,",
            "parameters" => $data
        ]);

    }

    public function generate( Request $request )
    {
        $data = $request->all();

        // region existence check
        if (isset($data['id'])) {
            $does_exist = $this->form->find($data['id']);

            if (!$does_exist) {
                return response()->json([
                    'code'  => 404,
                    'message' => 'Form ID does not exist.',
                ], 404);
            }
        }
        // endregion existence check

        // region Operation

        DB::beginTransaction();
        try {
            if (isset($data['id'])) {
                if(isset($data['meta'])){
                    $record = Meta::where('target_type', 'forms')
                        ->where('target_id', $data['id']);
                    if($record){
                        $record->forceDelete();
                    }
                }

                if(isset($data['flow_control_request'])){
                    $record = FlowControlRequest::where('form_id', $data['id']);
                    if($record){
                        foreach($record->get() as $value) {
                            $value->approvers()->forceDelete();
                        }

                        $record->forceDelete();
                    }
                }

                if(isset($data['file'])){
                    $record = File::where('target_type', 'forms')
                        ->where('target_id', $data['id']);
                    if($record){
                        $record->forceDelete();
                    }
                }

            }

            $form_definition = $this->define( new Request($data));

            if(isset($form_definition) && !is_code_success( $form_definition->status() ) ){
                return response()->json([
                    "code" => 500,
                    "message" => $form_definition->getData()->message,
                ], 500);
            }

            $form_id = $form_definition->getData()->data->forms->id;
            if(isset($data['meta'])
                && is_array($data['meta'])){
                foreach ($data['meta'] as $key => $value) {
                    if(is_array($value)){
                        if(empty($value)) continue;
                        $meta_definition = $this->meta_controller->define( new Request([
                            'target_type' => $this->meta_index,
                            'target_id' => $form_id,
                            'meta_key' => 'meta_array',
                            'meta_value' => $key,
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
                        $meta_id = $meta_definition->getData()->data->metas->id;
                        foreach( $value as $key_ => $value_ ){
                            foreach($value_ as $key__ => $value__){
                                $meta_definition = $this->meta_controller->define( new Request([
                                    'target_type' => 'form_array',
                                    'target_id' => $meta_id,
                                    'meta_key' => $key_.'_'.$key__,
                                    'meta_value' => $value__,
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

                        }

                    } else {
                        $meta_definition = $this->meta_controller->define( new Request([
                            'target_type' => $this->meta_index,
                            'target_id' => $form_id,
                            'meta_key' => $key,
                            'meta_value' => $value,
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

                }
            }

            if(isset($data['flow_control_request'])
                && is_array($data['flow_control_request'])){
                $data['flow_control_request']['fresh'] = true;
                $data['flow_control_request']['form_id'] = $form_id;
                if(!isset($data['flow_control_request']['name'])){
                    $data['flow_control_request']['name'] = '';
                }
                $flow_control_request_data = $data['flow_control_request'];
                $flow_control_request_data['added_by'] = $data['added_by'] ?? 0;
                $flow_control_request_definition = $this->flow_control_request_controller->define( new Request($flow_control_request_data));
                if( isset($flow_control_request_definition)
                    && !is_code_success( $flow_control_request_definition->status() ) ){
                    DB::rollback();
                    return response()->json($flow_control_request_definition->getData(), 500);
                }
            }

            if(isset($data['file'])
                && is_array($data['file'])){
                foreach ($data['file'] as $value) {
                    $value['fresh'] = true;
                    $value['added_by'] = $data['added_by'] ?? 0;
                    $file_definition = $this->file_controller->define( new Request($value));

                    if( isset($file_definition)
                    && !is_code_success( $file_definition->status() ) ){
                        DB::rollback();
                        return response()->json([
                            "code" => 500,
                            "message" => $file_definition->getData()->message
                        ], 500);
                    }

                }
            }

            $form_requester = [
                'link' => '',
                'password' => ''
            ];
            if( isset($flow_control_request_definition)
                && is_code_success( $flow_control_request_definition->status() )
            ){
                $flow_control_request_approver = new FlowControlRequestApprover();

                $flow_control_request_approver_ = $flow_control_request_approver::where('flow_control_request_id', $flow_control_request_definition->getData()->data->flow_control_request->id)->get();

                foreach( $flow_control_request_approver_->toArray() as $value ){
                    if($value['name'] == 'requested'){
                        $link = $this->meta_controller->fetchByTarget( new Request([
                            'target_type' => 'request_approver',
                            'target_id' => $value['id'],
                            'meta_key' => 'link',
                            'single' => true
                        ]));
                        if(isset($data['type_id']) && $data['type_id'] == 6){
                            $approver = Approver::find($value["approver_id"]);
                            if( !$approver ) {
                                DB::rollback();
                                return response()->json([
                                    'code'  => 500,
                                    'message' => 'Something went wrong. Name does not exist.'
                                ], 500);
                            }
                            $meta_definition = $this->meta_controller->define( new Request([
                                'target_type' => $this->meta_index,
                                'target_id' => $form_id,
                                'meta_key' => 'full_name',
                                'meta_value' => $approver->toArray()['name'],
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
                        if( isset($link)
                            && is_code_success( $link->status() ) ){
                            $form_requester['link'] = $link->getData()->data->metas->meta_value;
                        }

                        $password = $this->meta_controller->fetchByTarget( new Request([
                            'target_type' => 'request_approver',
                            'target_id' => $value['id'],
                            'meta_key' => 'password',
                            'single' => true
                        ]));

                        if( isset($password)
                            && is_code_success( $password->status() ) ){
                            $form_requester['password'] = $password->getData()->data->metas->meta_value;
                        }

                        break;
                    }

                }

            }
            DB::commit();
            return response()->json([
                'code'  => 200,
                'message' => 'Form generated successfully.',
                'data' => [
                    'forms' => $form_definition->getData()->data->forms,
                    'requester' => $form_requester
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'code'  => 500,
                'message' => 'Something went wrong.',
                'description' => $e->getMessage()
            ], 500);

        }

        // endregion Operation

    }

    public function defineMeta( Request $request )
    {
        $data = $request->all();

        // region existence check
        if (!isset($data['id'])) {
            return response()->json([
                'code'  => 404,
                'message' => 'Form ID is not set.',
            ], 404);
        }

        $does_exist = $this->form->find($data['id']);

        if (!$does_exist) {
            return response()->json([
                'code'  => 404,
                'message' => 'Form ID does not exist.',
            ], 404);
        }
        // endregion existence check

        // region Operation

        DB::beginTransaction();
        try {
            if (isset($data['id'])) {
                if(isset($data['meta'])){
                    $record = Meta::where('target_type', 'forms')
                        ->where('target_id', $data['id']);
                    if($record){
                        $record->forceDelete();
                    }
                }

            }

            if(isset($data['file_link'])){
                Form::where('id', $data['id'])->update(['form_link' => $data['file_link']]);
            }

            if(isset($data['meta'])
                && is_array($data['meta'])){
                foreach ($data['meta'] as $key => $value) {
                    if (isset($data['id'])) {
                        $record = Meta::where('target_type', $this->meta_index)
                            ->where('target_id', $data['id'])
                            ->where('meta_key', $key)
                            ->where('meta_value', $value);
                        if($record){
                            $record->forceDelete();
                        }

                    }
                    if(is_array($value)){

                        if(empty($value)) continue;
                        $meta_definition = $this->meta_controller->define( new Request([
                            'target_type' => $this->meta_index,
                            'target_id' => $data['id'],
                            'meta_key' => 'meta_array',
                            'meta_value' => $key,
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
                        $meta_id = $meta_definition->getData()->data->metas->id;
                        foreach( $value as $key_ => $value_ ){
                            foreach($value_ as $key__ => $value__){
                                $meta_definition = $this->meta_controller->define( new Request([
                                    'target_type' => 'form_array',
                                    'target_id' => $meta_id,
                                    'meta_key' => $key_.'_'.$key__,
                                    'meta_value' => $value__,
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

                        }

                    } else {
                        $meta_definition = $this->meta_controller->define( new Request([
                            'target_type' => $this->meta_index,
                            'target_id' => $data['id'],
                            'meta_key' => $key,
                            'meta_value' => $value,
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

                }
            }

            DB::commit();
            return response()->json([
                "code" => 200,
                "message" => 'Form updated successfully.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'code'  => 500,
                'message' => 'Something went wrong.',
                'description' => $e->getMessage()
            ], 500);

        }

        // endregion Operation

    }

    public function defineFlowControl( Request $request )
    {
        $data = $request->all();

        // region existence check
        if (isset($data['id'])) {
            $does_exist = FlowControlRequest::find($data['id']);

            if (!$does_exist) {
                return response()->json([
                    'code'  => 404,
                    'message' => 'Form ID does not exist.',
                ], 404);
            }
        }
        // endregion existence check

        // region Operation

        DB::beginTransaction();
        try {
            if(isset($data['flow_control_request'])
                && is_array($data['flow_control_request'])){
                $flow_control_request_id = $data['id'];
                $data['flow_control_request']['fresh'] = true;
                $data['flow_control_request']['form_id'] = $data['id'];
                $flow_control_request_data = $data['flow_control_request'];
                $flow_control_request_data['added_by'] = $data['added_by'] ?? 0;
                if (isset($flow_control_request_data['flow_control_request_approver'])) {
                    $flow_control_request = FlowControlRequest::with('approvers')
                        ->where('id', $data['id'])->first();
                    $approver_ids = array_column($flow_control_request_data['flow_control_request_approver'], 'approver_id');
                    if($approver_ids != array_unique($approver_ids)){
                        DB::rollback();
                        return response()->json([
                            'code' => 500,
                            'message' => 'Failed to assign a signatory. Make sure the signatory is not assigned twice.',
                        ], 500);
                    }
                    foreach ($flow_control_request_data['flow_control_request_approver'] as $key => $value) {
                        if(!isset($value['name'])){
                            DB::rollback();
                            return response()->json([
                                'code' => 404,
                                'message' => "Name is not set.",
                            ], 404);
                        }

                        $approver = Approver::where('id',$value["approver_id"])->first();
                        if (!$approver) {
                            DB::rollback();
                            return response()->json([
                                'code' => 404,
                                'message' => "Approver does not exist.",
                            ], 404);
                        }

                        foreach($flow_control_request->toArray()['approvers'] as $value_){
                            if( $value['name'] == $value_['name'] ){
                                if($value_['approval_status'] !== 'false') {
                                    DB::rollback();
                                    return response()->json([
                                        'code' => 500,
                                        'message' => 'Failed to update a signatory. Request already '.($value_['approval_status']).'.',
                                    ], 500);
                                    continue 2;
                                } else {
                                    $record = FlowControlRequestApprover::find($value_['id']);
                                    if($record){
                                        $record->forceDelete();
                                    }
                                }
                            }
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

                        $flow_control_request_approver = $this->flow_control_request_approver_controller->define(new Request([
                            'approver_id' => $value['approver_id'],
                            'name' => $value['name'],
                            'flow_control_request_id' => $flow_control_request_id,
                            'approval_status' => 'false',
                            'override_reject' => 'false',
                            'override_accept' => 'false',
                            'required' => $required,
                            'notify' => $notify,
                            'approvestatus' => $approvestatus,
                            'approveoverride' => $approveoverride,
                            'added_by' => $data['added_by'] ?? 0,
                            'fresh' => true,
                        ]), $flow_control_request_id);

                        if (!is_code_success($flow_control_request_approver->status())) {
                            DB::rollback();
                            return response()->json([
                                'code' => 500,
                                'message' => $flow_control_request_approver->getData(),
                            ], 500);
                        }

                    }

                    $result = $this->flow_control_request_controller->enhancedBuild($flow_control_request_id);
                    if(isset($result) && !is_code_success( $result->status() ) ){
                        DB::rollback();
                        return response()->json([
                            "code" => 500,
                            "data" => $result->getData()
                        ], 500);
                    }

                }

               if( isset($flow_control_request_definition)
                    && !is_code_success( $flow_control_request_definition->status() ) ){
                    DB::rollback();
                    return response()->json($flow_control_request_definition->getData(), 500);
                }
            }

            DB::commit();
            return response()->json([
                "code" => 200,
                "message" => 'Form updated successfully.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'code'  => 500,
                'message' => 'Something went wrong.',
                'description' => $e->getMessage()
            ], 500);

        }

        // endregion Operation

    }

    public function status( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;

        //region existence check
        if (!isset($data['id'])) {
            if( !is_numeric( $data['id'] ) || $data['id'] <= 0 ){
                return response()->json([
                    'message' => "Invalid Form ID.",
                ],  500);
            }
        }
        $does_exist = $this->form->find($data['id']);

        if (!$does_exist) {
            return response()->json([
                'code'  => 404,
                'message' => 'Form ID does not exist.',
            ], 404);
        }
        //endregion existence check

        $form = $this->form->where('id', $data['id']);

        if(isset($data['relationship'])) {
            $form = $form->with($data['relationship']);
        }

        $form = $form->first();

        $flow_control_request = new FlowControlRequest();
        $flow_control_request = $flow_control_request::where('form_id', $form->id)->with('forms')->first();

        $meta_value = [];

        foreach(['forms', 'flow_control_request'] as $value){
            if($value == 'forms'){
                $meta_controller = $this->meta_controller->fetchByTarget(new Request([
                    'target_id' => $form->id,
                    'target_type' => $value,
                ]));
            } else {
                if(!$flow_control_request) continue;
                $meta_controller = $this->meta_controller->fetchByTarget(new Request([
                    'target_id' => $flow_control_request->id,
                    'target_type' => $value,
                ]));
            }

            if( isset($meta_controller)
                && !is_code_success( $meta_controller->status() ) ){
                $meta_controller = [];
            } else {
                $meta_controller = $meta_controller->getData()->data->metas;
            }
            if(!empty($meta_controller)){
                foreach($meta_controller as $value_ ){
                    if($value_->meta_key === 'approved_signature'){
                        $response = \Storage::get($value_->meta_value);
                        $dataUri = 'data:image/'.  'PNG' . ';base64,' . base64_encode($response);
                        $meta_value['meta_' . $value_->meta_key] = $dataUri;
                    } else if($value_->meta_key === 'requested_signature'){
                        $response = \Storage::get($value_->meta_value);
                        $dataUri = 'data:image/'.  'PNG' . ';base64,' . base64_encode($response);
                        $meta_value['meta_' . $value_->meta_key] = $dataUri;
                    } else if($value_->meta_key === 'certified_signature'){
                        $response = \Storage::get($value_->meta_value);
                        $dataUri = 'data:image/'.  'PNG' . ';base64,' . base64_encode($response);
                        $meta_value['meta_' . $value_->meta_key] = $dataUri;
                    } else if($value_->meta_key === 'job_order_no'){
                        $value = $value_->meta_value;
                        $record = Meta::where('target_id', '!=', $value_->target_id)
                            ->where('meta_key', 'job_order_no')
                            ->where('meta_value', $value)->pluck('target_id')->first();
                        if($record){
                            $meta_value['meta_job_order_form_id'] = $record;
                        }
                        $meta_value['meta_' . $value_->meta_key] = $value_->meta_value;
                    } else if($value_->meta_key === 'meta_array'){
                        $meta_value['meta_' . $value_->meta_value] = [];

                        $meta_array = $this->meta_controller->fetchByTarget(new Request([
                            'target_id' => $value_->id,
                            'target_type' => 'form_array'
                        ]));

                        if( isset($meta_array)
                            && !is_code_success( $meta_array->status() ) ){
                            return response()->json([
                                "code"       => 500,
                                "message"      => "Invalid meta array.",
                            ], 500);
                        }
                        $meta_array_value = $meta_array->getData()->data->metas;
                        foreach($meta_array_value as $value){
                            $result_array = explode('_', $value->meta_key);
                            $result_array_name = array_shift($result_array);
                            $result_array = implode('_', $result_array);
                            $meta_value['meta_' . $value_->meta_value][$result_array_name][$result_array] = $value->meta_value;
                        }

                    } else {
                        $meta_value['meta_' . $value_->meta_key] = $value_->meta_value;
                    }
                }
            }
        }

        if( $flow_control_request ){
            $flow_control_request_approver = new FlowControlRequestApprover();
            $flow_control_request_approver = $flow_control_request_approver::with('approver')->where('flow_control_request_id', $flow_control_request->id)->get();
            $flow_control_request_approver_status = $this->flow_control_request_approver_controller
                ->fetchRequestApproversStatus(new Request(), $flow_control_request->id);
            if(isset($flow_control_request_approver_status) && !is_code_success( $flow_control_request_approver_status->status() ) ){
                return response()->json([
                    "code" => 500,
                    "message" => $flow_control_request_approver_status->getData()->message,
                ], 500);
            }
            $flow_control_request_approver_status = $flow_control_request_approver_status->getData()->data;

        } else {
            $flow_control_request = [];
            $flow_control_request_approver = [];
            $flow_control_request_approver_status = [];
        }
        $is_director = false;
        $request_approver_id = null;
        if( isset($data['user_id']) ){
            $record = User::with('employee')
                ->with('approver')
                ->with('logs')
                ->where('id', $data['user_id'])->first();
            if(isset($record['approver'])) {
                $signature = Approver::where('id', $record->approver->approver_id)->first();
                if($flow_control_request){
                    $request_approver = FlowControlRequestApprover::where('approver_id', $signature->id)
                        ->where('flow_control_request_id', $flow_control_request->id)
                        ->where('approval_status', 'false')
                        ->first();
                    if($request_approver){
                        $request_approver_id = $request_approver->id;
                    }
                }
                if($signature->type === 'SDMD Director'){
                    $is_director = true;
                }
            }

        }


        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved form.",
            "data"       => [
                $this->meta_index => $form,
                'flow_control_request' => $flow_control_request,
                'request_approver' => $flow_control_request_approver,
                'request_approver_status' => $flow_control_request_approver_status,
                'metas' => $meta_value,
                "parameters" => $data,
                'is_director' => $is_director,
                'request_approver_id' => $request_approver_id
            ]
        ]);

    }

    public function continueStatus( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;

        //region existence check
        if (!isset($data['id'])) {
            if( !is_numeric( $data['id'] ) || $data['id'] <= 0 ){
                return response()->json([
                    'message' => "Invalid Form ID.",
                ],  500);
            }
        }
        $form = $this->form->find($data['id']);

        if (!$form) {
            return response()->json([
                'code'  => 404,
                'message' => 'Form ID does not exist.',
            ], 404);
        }
        //endregion existence check

        $form = $this->form->with('type')->where('id', $data['id'])->first();
        if($form['type_id'] !== 1) {
            return response()->json([
                'code'  => 500,
                'message' => 'Invalid ICT Service Request.',
            ], 404);
        }

        $job_order = JobOrderCount::where('month', date('m'))->where('year', date('Y'))->first();

        if($job_order == null){
            $job_order = JobOrderCount::create([
                'year' => date('Y'),
                'month' => date('m'),
                'count' => 1,
                'created_at' => now()
            ]);
        }

        $job_order = "JO-".date('Y')."-".date('m')."-".$job_order->count;

        $flow_control_request = new FlowControlRequest();
        $flow_control_request = $flow_control_request::where('form_id', $form->id)->with('forms')->first();

        $meta_value = [];

        foreach(['forms', 'flow_control_request'] as $value){
            if($value == 'forms'){
                $meta_controller = $this->meta_controller->fetchByTarget(new Request([
                    'target_id' => $form->id,
                    'target_type' => $value,
                ]));
            } else {
                if(!$flow_control_request) continue;
                $meta_controller = $this->meta_controller->fetchByTarget(new Request([
                    'target_id' => $flow_control_request->id,
                    'target_type' => $value,
                ]));
            }

            if( isset($meta_controller)
                && !is_code_success( $meta_controller->status() ) ){
                $meta_controller = [];
            } else {
                $meta_controller = $meta_controller->getData()->data->metas;
            }
            if(!empty($meta_controller)){
                foreach($meta_controller as $value_ ){
                    if($value_->meta_key === 'approved_signature'){
                        $response = \Storage::get($value_->meta_value);
                        $dataUri = 'data:image/'.  'PNG' . ';base64,' . base64_encode($response);
                        $meta_value['meta_' . $value_->meta_key] = $dataUri;
                    } else if($value_->meta_key === 'requested_signature'){
                        $response = \Storage::get($value_->meta_value);
                        $dataUri = 'data:image/'.  'PNG' . ';base64,' . base64_encode($response);
                        $meta_value['meta_' . $value_->meta_key] = $dataUri;
                    } else if($value_->meta_key === 'certified_signature'){
                        $response = \Storage::get($value_->meta_value);
                        $dataUri = 'data:image/'.  'PNG' . ';base64,' . base64_encode($response);
                        $meta_value['meta_' . $value_->meta_key] = $dataUri;
                    } else if($value_->meta_key === 'meta_array'){
                        $meta_value['meta_' . $value_->meta_value] = [];

                        $meta_array = $this->meta_controller->fetchByTarget(new Request([
                            'target_id' => $value_->id,
                            'target_type' => 'form_array'
                        ]));

                        if( isset($meta_array)
                            && !is_code_success( $meta_array->status() ) ){
                            return response()->json([
                                "code"       => 500,
                                "message"      => "Invalid meta array.",
                            ], 500);
                        }
                        $meta_array_value = $meta_array->getData()->data->metas;
                        foreach($meta_array_value as $value){
                            $result_array = explode('_', $value->meta_key);
                            $result_array_name = array_shift($result_array);
                            $result_array = implode('_', $result_array);
                            $meta_value['meta_' . $value_->meta_value][$result_array_name][$result_array] = $value->meta_value;
                        }

                    } else {
                        $meta_value['meta_' . $value_->meta_key] = $value_->meta_value;
                    }
                }
            }
        }

        if(isset($meta_value['meta_job_order_no'])){
            $existing_id = Meta::where('target_type', 'forms')
                ->where('target_id', '!=', $form['id'])
                ->where('meta_key', 'job_order_no')
                ->where('meta_value', $meta_value['meta_job_order_no'])
                ->pluck('target_id')->first();
            if( $existing_id ){
                return response()->json([
                    'code'  => 404,
                    'message' => 'Job Order No. already assigned.',
                    'data' => [
                        'id' => $existing_id
                    ]
                ], 404);
            }

        }

        if( $flow_control_request ){
            $flow_control_request_approver = new FlowControlRequestApprover();
            $flow_control_request_approver = $flow_control_request_approver::with('approver')->where('flow_control_request_id', $flow_control_request->id)->get();
            $flow_control_request_approver_status = $this->flow_control_request_approver_controller
                ->fetchRequestApproversStatus(new Request(), $flow_control_request->id);
            if(isset($flow_control_request_approver_status) && !is_code_success( $flow_control_request_approver_status->status() ) ){
                return response()->json([
                    "code" => 500,
                    "message" => $flow_control_request_approver_status->getData()->message,
                ], 500);
            }
            $flow_control_request_approver_status = $flow_control_request_approver_status->getData()->data;

        } else {
            $flow_control_request = [];
            $flow_control_request_approver = [];
            $flow_control_request_approver_status = [];
        }
        $is_director = false;
        if( isset($data['user_id']) ){
            $record = User::with('employee')
                ->with('approver')
                ->with('logs')
                ->where('id', $data['user_id'])->first();
            if(isset($record['approver'])) {

                $signature = Approver::where('id', $record->approver->approver_id)->first();
                if($signature->type === 'SDMD Director'){
                    $is_director = true;
                }
            }

        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved form.",
            "data"       => [
                $this->meta_index => $form,
                'flow_control_request' => $flow_control_request,
                'request_approver' => $flow_control_request_approver,
                'request_approver_status' => $flow_control_request_approver_status,
                'metas' => $meta_value,
                "parameters" => $data,
                'is_director' => $is_director,
                'job_order' => $job_order
            ]
        ]);

    }

    public function continueGenerate( Request $request )
    {
        $data = $request->all();

        // region existence check
        if (isset($data['id'])) {
            $does_exist = $this->form->find($data['id']);

            if (!$does_exist) {
                return response()->json([
                    'code'  => 404,
                    'message' => 'Form ID does not exist.',
                ], 404);
            }
        }

        if (!isset($data['job_order_no'])) {
            return response()->json([
                'code'  => 404,
                'message' => 'Job Order No. is not set.',
            ], 404);
        }

        if (!isset($data['form_id'])) {
            return response()->json([
                'code'  => 404,
                'message' => 'Service Request Form ID is not set.',
            ], 404);
        }

        $does_exist = $this->form->find($data['form_id']);

        if (!$does_exist) {
            return response()->json([
                'code'  => 404,
                'message' => 'Service Request Form ID does not exist.',
            ], 404);
        }
        // endregion existence check

        // region Operation

        DB::beginTransaction();
        try {
            if (isset($data['id'])) {
                if(isset($data['meta'])){
                    $record = Meta::where('target_type', 'forms')
                        ->where('target_id', $data['id']);
                    if($record){
                        $record->forceDelete();
                    }
                }

                if(isset($data['flow_control_request'])){
                    $record = FlowControlRequest::where('form_id', $data['id']);
                    if($record){
                        foreach($record->get() as $value) {
                            $value->approvers()->forceDelete();
                        }

                        $record->forceDelete();
                    }
                }

                if(isset($data['file'])){
                    $record = File::where('target_type', 'forms')
                        ->where('target_id', $data['id']);
                    if($record){
                        $record->forceDelete();
                    }
                }

            }

            $form_definition = $this->define( new Request($data));

            if(isset($form_definition) && !is_code_success( $form_definition->status() ) ){
                return response()->json([
                    "code" => 500,
                    "message" => $form_definition->getData()->message,
                ], 500);
            }

            $form_id = $form_definition->getData()->data->forms->id;
            if(isset($data['meta'])
                && is_array($data['meta'])){
                foreach ($data['meta'] as $key => $value) {
                    if(is_array($value)){
                        if(empty($value)) continue;
                        $meta_definition = $this->meta_controller->define( new Request([
                            'target_type' => $this->meta_index,
                            'target_id' => $form_id,
                            'meta_key' => 'meta_array',
                            'meta_value' => $key,
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
                        $meta_id = $meta_definition->getData()->data->metas->id;
                        foreach( $value as $key_ => $value_ ){
                            foreach($value_ as $key__ => $value__){
                                $meta_definition = $this->meta_controller->define( new Request([
                                    'target_type' => 'form_array',
                                    'target_id' => $meta_id,
                                    'meta_key' => $key_.'_'.$key__,
                                    'meta_value' => $value__,
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

                        }

                    } else {
                        $meta_definition = $this->meta_controller->define( new Request([
                            'target_type' => $this->meta_index,
                            'target_id' => $form_id,
                            'meta_key' => $key,
                            'meta_value' => $value,
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

                }
            }

            if(isset($data['flow_control_request'])
                && is_array($data['flow_control_request'])){
                $data['flow_control_request']['fresh'] = true;
                $data['flow_control_request']['form_id'] = $form_id;
                if(!isset($data['flow_control_request']['name'])){
                    $data['flow_control_request']['name'] = '';
                }
                $flow_control_request_data = $data['flow_control_request'];
                $flow_control_request_data['added_by'] = $data['added_by'] ?? 0;
                $flow_control_request_definition = $this->flow_control_request_controller->define( new Request($flow_control_request_data));
                if( isset($flow_control_request_definition)
                    && !is_code_success( $flow_control_request_definition->status() ) ){
                    DB::rollback();
                    return response()->json($flow_control_request_definition->getData(), 500);
                }
            }

            if(isset($data['file'])
                && is_array($data['file'])){
                foreach ($data['file'] as $value) {
                    $value['fresh'] = true;
                    $value['added_by'] = $data['added_by'] ?? 0;
                    $file_definition = $this->file_controller->define( new Request($value));

                    if( isset($file_definition)
                        && !is_code_success( $file_definition->status() ) ){
                        DB::rollback();
                        return response()->json([
                            "code" => 500,
                            "message" => $file_definition->getData()->message
                        ], 500);
                    }

                }
            }

            $form_requester = [
                'link' => '',
                'password' => ''
            ];
            if( isset($flow_control_request_definition)
                && is_code_success( $flow_control_request_definition->status() )
            ){
                $flow_control_request_approver = new FlowControlRequestApprover();

                $flow_control_request_approver_ = $flow_control_request_approver::where('flow_control_request_id', $flow_control_request_definition->getData()->data->flow_control_request->id)->get();

                foreach( $flow_control_request_approver_->toArray() as $value ){
                    if($value['name'] == 'requested'){
                        $link = $this->meta_controller->fetchByTarget( new Request([
                            'target_type' => 'request_approver',
                            'target_id' => $value['id'],
                            'meta_key' => 'link',
                            'single' => true
                        ]));

                        if( isset($link)
                            && is_code_success( $link->status() ) ){
                            $form_requester['link'] = $link->getData()->data->metas->meta_value;
                        }

                        $password = $this->meta_controller->fetchByTarget( new Request([
                            'target_type' => 'request_approver',
                            'target_id' => $value['id'],
                            'meta_key' => 'password',
                            'single' => true
                        ]));

                        if( isset($password)
                            && is_code_success( $password->status() ) ){
                            $form_requester['password'] = $password->getData()->data->metas->meta_value;
                        }

                        break;
                    }

                }

            }

            foreach([$data['form_id'], $form_id] as $value ){
                $meta_definition = $this->meta_controller->define( new Request([
                    'target_type' => $this->meta_index,
                    'target_id' => $value,
                    'meta_key' => 'job_order_no',
                    'meta_value' => $data['job_order_no'],
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

            $job_order_count = DB::table('job_order_count')
                ->select('count')
                ->where('month', date('m'))
                ->where('year', date('Y'))
                ->pluck('count')
                ->first();
            if($job_order_count === null){
                DB::rollback();
                return response()->json([
                    'code'  => 500,
                    'message' => 'Failed to retrieve job order'
                ], 500);
            }
            $job_order_count = $job_order_count + 1;
            JobOrderCount::updateOrCreate(
                ['month' => date('m'), 'year' => date('Y')],
                ['count' => $job_order_count]
            );
            DB::commit();
            return response()->json([
                'code'  => 200,
                'message' => 'Form generated successfully.',
                'data' => [
                    'forms' => $form_definition->getData()->data->forms,
                    'requester' => $form_requester
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'code'  => 500,
                'message' => 'Something went wrong.',
                'description' => $e->getMessage()
            ], 500);

        }

        // endregion Operation

    }

    public function linkForm( Request $request )
    {
        $data = $request->all();

        if(!isset($data['link'])){
            return response()->json([
                'code' => 404,
                'message' => "Link is not set."
            ], 404);
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
        $request_approver_id = $link->getData()->data->metas->target_id;

        $flow_control_request_approver = $this->flow_control_request_approver_controller
            ->fetch(new Request(['single' => true]), $request_approver_id);
        if(isset($flow_control_request_approver) && !is_code_success( $flow_control_request_approver->status() ) ){
            return response()->json([
                "code" => 500,
                "message" => $flow_control_request_approver->getData()->message,
            ], 500);
        }
        $flow_control_request_approver = $flow_control_request_approver->getData()->data->flow_control_request_approver;
        $flow_control_request = $flow_control_request_approver->flow_control_request_id;


        $flow_control_request = FlowControlRequest::find($flow_control_request);

        if(!$flow_control_request){
            return response()->json([
                "code" => 500,
                "message" => 'Flow Control ID does not exist.'
            ], 500);
        }

        $result = $this->status(new Request(['relationship' => 'type']), $flow_control_request->form_id);

        return response()->json($result->getData());

    }

    public function joForm( Request $request )
    {
        $data = $request->all();

        if(!isset($data['job_order_no'])){
            return response()->json([
                'code' => 404,
                'message' => "Job Order No is not set."
            ], 404);
        }

        $record = Meta::where('meta_key', 'job_order_no')
            ->where('meta_value', $data['job_order_no']);

        if($record->doesntExist()){
            return response()->json([
                'code'  => 500,
                'message' => 'Job Order No does not exists.'
            ], 500);
        }

        $record = $record->get()->toArray();
        $initial = 'i';
        foreach($record as $record_data){
            $form = $this->form->with('type')->where('id', $record_data['target_id'])->first();

            $flow_control_request = new FlowControlRequest();
            $flow_control_request = $flow_control_request::where('form_id', $form->id)->with('forms')->first();

            foreach($form->toArray() as $key => $value){
                if($key == 'type'){
                    $meta_value[$initial .'_name'] = $value['form_no'];
                } else {
                    $meta_value[$initial .'_' . $key] = $value;
                }
            }

            foreach(['forms', 'flow_control_request'] as $value){
                if($value == 'forms'){
                    $meta_controller = $this->meta_controller->fetchByTarget(new Request([
                        'target_id' => $form->id,
                        'target_type' => $value,
                    ]));
                } else {
                    if(!$flow_control_request) continue;
                    $meta_controller = $this->meta_controller->fetchByTarget(new Request([
                        'target_id' => $flow_control_request->id,
                        'target_type' => $value,
                    ]));
                }

                if( isset($meta_controller)
                    && !is_code_success( $meta_controller->status() ) ){
                    $meta_controller = [];
                } else {
                    $meta_controller = $meta_controller->getData()->data->metas;
                }
                if(!empty($meta_controller)){
                    foreach($meta_controller as $value_ ){
                        if($value_->meta_key === 'approved_signature'){
                            $response = \Storage::get($value_->meta_value);
                            $dataUri = 'data:image/'.  'PNG' . ';base64,' . base64_encode($response);
                            $meta_value[$initial . '_meta_' . $value_->meta_key] = $dataUri;
                        } else if($value_->meta_key === 'requested_signature'){
                            $response = \Storage::get($value_->meta_value);
                            $dataUri = 'data:image/'.  'PNG' . ';base64,' . base64_encode($response);
                            $meta_value[$initial . '_meta_' . $value_->meta_key] = $dataUri;
                        } else if($value_->meta_key === 'certified_signature'){
                            $response = \Storage::get($value_->meta_value);
                            $dataUri = 'data:image/'.  'PNG' . ';base64,' . base64_encode($response);
                            $meta_value[$initial . '_meta_' . $value_->meta_key] = $dataUri;
                        } else if($value_->meta_key === 'job_order_no'){
                            $value = $value_->meta_value;
                            $record = Meta::where('target_id', '!=', $value_->target_id)
                                ->where('meta_key', 'job_order_no')
                                ->where('meta_value', $value)->pluck('target_id')->first();
                            if($record){
                                $meta_value[$initial . '_meta_job_order_form_id'] = $record;
                            }
                            $meta_value[$initial . '_meta_' . $value_->meta_key] = $value_->meta_value;
                        } else if($value_->meta_key === 'meta_array'){
                            $meta_value[$initial . '_meta_' . $value_->meta_value] = [];

                            $meta_array = $this->meta_controller->fetchByTarget(new Request([
                                'target_id' => $value_->id,
                                'target_type' => 'form_array'
                            ]));

                            if( isset($meta_array)
                                && !is_code_success( $meta_array->status() ) ){
                                return response()->json([
                                    "code"       => 500,
                                    "message"      => "Invalid meta array.",
                                ], 500);
                            }
                            $meta_array_value = $meta_array->getData()->data->metas;
                            foreach($meta_array_value as $value){
                                $result_array = explode('_', $value->meta_key);
                                $result_array_name = array_shift($result_array);
                                $result_array = implode('_', $result_array);
                                $meta_value[$initial . '_meta_' . $value_->meta_value][$result_array_name][$result_array] = $value->meta_value;
                            }

                        } else {
                            $meta_value[$initial . '_meta_' . $value_->meta_key] = $value_->meta_value;
                        }
                    }
                }
            }

            $initial .= "i";
            $meta_result = $meta_value;

        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved job order form details.",
            "data"       => [
                'result' => $meta_result,
                "parameters" => $data,
            ]
        ]);

    }

    public function reportsEmail( Request $request )
    {
        $data = $request->all();

        if( !isset($data['from']) || !isset($data['to']) ){
            $meta = Meta::where('meta_key', 'email_date_created')->pluck('target_id');
        } else {
             $meta = Meta::where('meta_key', 'email_date_created')->whereBetween('meta_value', [$data['from'], $data['to']])->pluck('target_id');
        }

        $meta_value = [];
        foreach( $meta as $value ){
            $form = $this->form
                ->with(['type', 'flowControl'])
                ->where('id', $value)
                ->whereHas('flowControl', function($q) {
                    $q->where('approval_status', '=', 'approved');
                })
                ->where('type_id', 6)->first();
            if( !$form ) continue;

            $meta_controller = $this->meta_controller->fetchByTarget(new Request([
                'target_id' => $value,
                'target_type' => 'forms',
            ]));

            if( isset($meta_controller)
                && !is_code_success( $meta_controller->status() ) ){
                $meta_controller = [];
            } else {
                $meta_controller = $meta_controller->getData()->data->metas;
            }
            if(!empty($meta_controller)){
                foreach($meta_controller as $value_ ){
                    $meta_value[$value]['meta_' . $value_->meta_key] = $value_->meta_value;
                }
            }
        }

        $meta_result = [];
        foreach($meta_value as $key => $value ){
            $meta_result[$key]['Date Requested'] = date('M d, Y', strtotime($value['meta_date_prepared']));
            $meta_result[$key]['Date Created'] = isset($value['meta_email_date_created']) ? date('M d, Y', strtotime($value['meta_email_date_created'])) : '';
            $meta_result[$key]['Name'] = $value['meta_full_name'];
            $meta_result[$key]['Email Registered'] = $value['meta_email_registered'] ?? '';
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved reports.",
            "data"       => [
                'result' => $meta_result,
                "parameters" => $data,
            ]
        ]);

    }

    public function reportsAccomplishment( Request $request )
    {
        $data = $request->all();
        $date = date('F j, Y');
        $position = null;

        if($request->user() !== null){
            $user_assign = UserAssign::where('user_id', $request->user()->id)->pluck('approver_id')->first();
            if($user_assign) $data['approver_id'] = $request->user()->id;

            $employee = User::with('employee')->where('id', $request->user()->id)->first();
            if($employee) {
                $employee = $employee->toArray()['employee'];
                $position = $employee['position'];
            }

        }

        if(!isset($data['approver_id'])){
            return response()->json([
                'code' => 404,
                'message' => "ID is not set."
            ], 404);
        }

        $user = Approver::find($data['approver_id']);
        if( !$user ){
            return response()->json([
                'code' => 404,
                'message' => "User full name is missing."
            ], 404);
        }

        if( !isset($data['from']) || !isset($data['to']) ){
            $model = $this->form
                ->with(['type', 'flowControl'])
                ->whereHas('flowControl', function($q) {
                    $q->where('approval_status', '=', 'approved');
                })
                ->where('type_id', 1);
        } else {
            $date = readable_date_range($data['from'], $data['to']);
            $model = $this->form
                ->with(['type', 'flowControl', 'flowControl.approvers'])
                ->whereHas('flowControl', function($q) use ($data) {
                    $q->where('approval_status', 'approved')->whereHas('approvers', function($q) use ($data) {
                        $q->where('name', '=', 'certified')->where('approver_id', '=', $data['approver_id']);
                    });
                })
                ->where('type_id', 1)
                ->whereBetween('created_at', [$data['from'], $data['to']]);
        }

        $form = $model->get()->toArray();
        $form_ids = $model->pluck('id')->toArray();
        $meta_result = [];
        foreach( $form_ids as $value ){
            $meta_controller = $this->meta_controller->fetchByTarget(new Request([
                'target_id' => $value,
                'target_type' => 'forms',
            ]));

            if( isset($meta_controller)
                && !is_code_success( $meta_controller->status() ) ){
                $meta_controller = [];
            } else {
                $meta_controller = $meta_controller->getData()->data->metas;
            }
            $meta_value = [];
            if(!empty($meta_controller)){
                foreach($meta_controller as $value_ ){
                    $meta_value['meta_' . $value_->meta_key] = $value_->meta_value;
                }
                if(isset($meta_value['meta_job_order_no'])){
                    $existing_id = Meta::where('target_type', 'forms')
                        ->where('target_id', '!=', $value)
                        ->where('meta_key', 'job_order_no')
                        ->where('meta_value', $meta_value['meta_job_order_no'])->first();
                    if( !$existing_id ){
                        return response()->json([
                            'code'  => 404,
                            'message' => 'Something went wrong. Job Order No. is missing.',
                            'data' => [
                                'job_order_no' => $meta_value['meta_job_order_no']
                            ]
                        ], 404);
                    }
                    $meta_controller = $this->meta_controller->fetchByTarget(new Request([
                        'target_id' => $existing_id->target_id,
                        'target_type' => 'forms',
                    ]));
                    $type_id = Form::find($existing_id->target_id);

                    // Skip if not form type id 2
                    if(!$type_id) continue;
                    if($type_id->type_id != 2) continue;

                    if( isset($meta_controller)
                        && !is_code_success( $meta_controller->status() ) ){
                        $meta_controller = [];
                    } else {
                        $meta_controller = $meta_controller->getData()->data->metas;
                    }
                    if(!empty($meta_controller)){
                        foreach($meta_controller as $value_ ){
                            $meta_value['meta_' . $value_->meta_key] = $value_->meta_value;
                        }
                    }

                }
                $meta_result[$value] = $meta_value;
            }
        }
        $result = [];
        foreach($meta_result as $key => $value ){
            $form_key = array_search($key, array_column($form, 'id'));
            if(!isset($form[$form_key])) continue;
            if(!isset($value['meta_job_order_no'])) continue;
            $approver_key = array_search("requested", array_column($form[$form_key]['flow_control']['approvers'], 'name'));
            $approver = Approver::find($form[$form_key]['flow_control']['approvers'][$approver_key]['approver_id']);
            if( !$approver ){
                return response()->json([
                    'code' => 404,
                    'message' => "Requester name is missing."
                ], 404);
            }
            $status = (!isset($value['meta_result'])) ? 'Pending' : ($value['meta_result'] == 'Repaired' ? 'Repaired': 'Pending');
            $result[$key]['Request Date'] = isset($value['meta_date_requested']) ? date('M d, Y', strtotime($value['meta_date_requested'])) : '';
            $result[$key]['Office'] = $value['meta_request_college'] ?? '';
            $result[$key]['Person'] = $approver['name'] ?? '';
            $result[$key]['Problem'] = $value['meta_request_details'] ?? '';
            $result[$key]['Status'] = $status;
            $result[$key]['Remarks'] = $value['meta_action_taken'] ?? '';
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved reports.",
            "data"       => [
                'date' => $date,
                'director' => "DR. ARIEL ROY L. REYES",
                'user' => ucfirst($user['name']),
                'user_position' => $position,
                'result' => $result,
                "parameters" => $data,
            ]
        ]);

    }

}
