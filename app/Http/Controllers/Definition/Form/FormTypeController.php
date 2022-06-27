<?php

namespace App\Http\Controllers\Definition\Form;

use DB;
use App\Data\Models\Definition\Form\FormType;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

class FormTypeController extends BaseController
{
    protected
        $form_type, $meta_index = 'form_type';

    function __construct(
        FormType $formType
    ){
        $this->form_type = $formType;
        // middleware
    }

    public function all()
    {

        $result = FormType::all();

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved form type.",
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

        $result = FormType::where('id', $id)->first();

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved form type.",
            "data"       => [
                $this->meta_index => $result,
                "parameters" => $data
            ]
        ]);

    }

    public function define( Request $request )
    {
        $data = $request->all();

        //region data validation
        foreach( ['name',
                     'revision_no',
                     'issue_no',
                     'date_effective'
                 ] as $value) {
            if( !isset( $data[$value] ) ) {
                return response()->json([
                    'code'  => 404,
                    'message' => $value .' is not set.',
                ], 404);
            }
        }

        $form_type = $this->makeModel ( $data, $this->form_type );

        $message = 'Successfully created form type.';

        //region insertion

        //region existence check
        if (isset($data['id'])) {
            $form_type = $this->form_type->find($data['id']);
            $message = 'Successfully updated form type.';

            if (!$form_type) {
                return response()->json([
                    'code'  => 404,
                    'message' => 'Form Type ID does not exist.',
                ], 404);
            }
        }
        //endregion existence check

        if(!$form_type->save($data)){
            return response()->json([
                "code" => 500,
                "message" => "Data Validation Error.",
                "description" => "An error was detected on one of the inputted data.",
                "data" =>   [
                    "errors" => $form_type->errors(),
                ]
            ], 500);
        }

        return response()->json([
            "code" => 200,
            "message" => $message,
            "data" => [
                $this->meta_index => $form_type,
            ]
        ]);
        //endregion insertion

    }

    public function delete( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;
        $record = FormType::find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No form type found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This form type does not exists",
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
                "message" => "Deleting form type was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Form Type was successfully deleted,",
            "parameters" => $data
        ]);

    }

}
