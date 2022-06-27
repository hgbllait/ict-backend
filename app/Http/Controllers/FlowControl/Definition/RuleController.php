<?php

namespace App\Http\Controllers\FlowControl\Definition;

use App\Data\Models\Definition\Rule;
use App\Data\Repositories\Definition\RuleRepository;
use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;


/**
 * Class RuleController
 * @package App\Http\Controllers\Definition
 */
class RuleController extends BaseController
{
    protected $rule, $entity, $meta_index = 'rule';

    public function __construct(
        Rule $rule
    ){
        $this->rule = $rule;
    }

    protected $fillable = [
        'employee_id',
        'rule_unique_id',
        'status',
    ];

    protected $column =[
        'id',
        'employee_id',
        'rule_unique_id',
        'description',
        'status',
    ];

    public function all( Request $request )
    {

        $result = $this->rule->all();

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

    public function define( Request $request )
    {
        $data = $request->all();

        if( isset( $data['id'] ) ){
            if( !is_numeric( $data['id'] ) || $data['id'] <= 0 ){
                return response()->json([
                    'message' => "Invalid rule ID.",
                ],  500);
            }
        }

        if( !isset( $data['rule_unique_id'] ) ){
            $data['rule_unique_id'] = sessioned_hash($data['employee_id']);
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

        $model = $this->makeModel ( $data, $this->rule );

        $message = 'Successfully created rule.';
        if( isset( $data['id'] ) ){
            $model  = $this->rule->find($data['id']);

            if( !$model){
                return response()->json([
                    'message' => "This rule does not exists",
                ], 404);
            }
            $message = 'Successfully update rule.';
        }

        if( !$model->save( $data ) ){
            $error_message = $model->errors();

            return response()->json([
                'message' => "rule definition was not successful.",
                'data' => [
                    'errors' => $error_message,
                ]
            ], 404);
        }

        return response()->json([
            'message' => $message,
            'messages' => [
                $this->meta_index => $model,
            ]
        ], 200);

    }

    // endregion Define

    // region Delete

    public function delete( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;
        $record = $this->rule->find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No rule found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This rule does not exists",
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
                "message" => "Deleting rule was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "rule was successfully deleted,",
            "parameters" => $data
        ]);

    }

    // endregion Delete

    // region Retrieve Data

    public function fetch( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;

        $result = $this->rule->where('id', $id)->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "rule not found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved rule.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
                "parameters" => $data
            ]
        ]);

    }

    // endregion Retrieve Data

}
