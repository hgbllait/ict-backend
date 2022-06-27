<?php

namespace App\Http\Controllers\Utilities\Meta;

use App\Data\Models\Request\FlowControlRequestApprover;
use App\Data\Models\Utilities\Meta\Meta;
use App\Http\Controllers\FlowControl\Request\FlowControlRequestApproverController;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

class MetaController extends BaseController
{

    protected
        $meta, $meta_index = 'metas';

    function __construct(
        Meta $meta
    ){
        $this->meta = $meta;
        // middleware
    }

    protected $fillable = [
        'target_type',
        'target_id',
        'meta_key',
        'meta_value',
    ];

    public function all()
    {

        $result = $this->meta->all();

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved meta.",
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

        $result = $this->meta->where('id', $id)->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "No meta are found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved meta.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
                "parameters" => $data
            ]
        ]);

    }

    public function fetchByTarget( Request $request )
    {
        $data = $request->all();

        $result = $this->meta;
        if(isset($data['target_type'])){
            $result = $result->where('target_type', $data['target_type']);
        }
        if(isset($data['target_id'])){
            $result = $result->where('target_id', $data['target_id']);
        }
        if(isset($data['meta_key'])){
            $result = $result->where('meta_key', $data['meta_key']);
        }
        if(isset($data['meta_value'])){
            $result = $result->where('meta_value', $data['meta_value']);
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
                "message"      => "No meta are found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved meta.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $count,
                "parameters" => $data
            ]
        ]);

    }

    public function fetchByApprovers( Request $request )
    {
        $data = $request->all();

        $result = $this->meta;
        if(isset($data['target_type'])){
            $result = $result->where('target_type', 'request_approver');
        }
        if(isset($data['meta_key'])){
            $result = $result->where('meta_key', $data['meta_key']);
        }
        if(isset($data['meta_value'])){
            $result = $result->where('meta_value', $data['meta_value']);
        }
        if(isset($data['single']) && $data['single']){
            $result = $result->first();
            $count = 1;
            if(!$result) $count = 0;
        } else {
            $result = $result->get();
            $count = $result->count();
        }


        foreach($result as $value){
            $value['approver'] = FlowControlRequestApprover::where('id', $value['target_id'])->with('approver')->first();

        }


        if ($count < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "No meta are found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved meta.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $count,
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
            $does_exist = $this->meta->find($data['id']);

            if (!$does_exist) {
                return response()->json([
                    'code'  => 404,
                    'message' => 'Meta ID does not exist.',
                ], 404);
            }
        }
        //endregion existence check

        $message = 'Successfully created meta.';

        //region insertion
        if (isset($data['id'])) {
            $model = $this->meta->find($data['id']);
            $message = 'Successfully updated meta.';

        } else {
            $model  = $this->meta
                ->where('target_type', $data['target_type'])
                ->where('target_id', $data['target_id'])
                ->where('meta_key', $data['meta_key'])
                ->first();
            if( !$model ){
                $model = $this->makeModel ( $data, $this->meta );
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
                "message" => "No meta found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This meta does not exists",
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
                "message" => "Deleting meta was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Meta was successfully deleted,",
            "parameters" => $data
        ]);

    }

}
