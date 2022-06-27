<?php

namespace App\Http\Controllers\Utilities\File;

use App\Data\Models\Utilities\Files\File;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

class FileController extends BaseController
{

    protected $file, $meta_index = 'files';

    function __construct(
        File $file
    ){
        $this->file = $file;
        // middleware
    }

    protected $fillable = [
        'target_type',
        'target_id',
        'name',
        'full_path',
    ];

    public function all()
    {

        $result = $this->file->all();

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved file.",
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

        $result = $this->file->where('id', $id)->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "No file are found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved file.",
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
            $does_exist = $this->file->find($data['id']);

            if (!$does_exist) {
                return response()->json([
                    'code'  => 404,
                    'message' => 'File ID does not exist.',
                ], 404);
            }
        }
        //endregion existence check

        $message = 'Successfully created file.';

        //region insertion
        if (isset($data['id'])) {
            $model = $this->file->find($data['id']);
            $message = 'Successfully updated file.';

        } else {
            $model  = $this->file
                ->where('target_type', $data['target_type'])
                ->where('target_id', $data['target_id'])
                ->where('name', $data['name'])
                ->first();
            if( !$model ){
                $model = $this->makeModel ( $data, $this->file );
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
        $record = $this->file->find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No file found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This file does not exists",
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
                "message" => "Deleting file was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "File was successfully deleted,",
            "parameters" => $data
        ]);

    }

    public function upload( Request $request )
    {

        $data = $request->all();

        $validation = $request->validate([
            'file'  =>  'required|file|image|mimes:jpeg,png,gif,jpg'
        ]);

        $file = $validation['file'];

        //region data validation
        foreach( ['approver_id', 'file'] as $value ){
            if(!isset($data[$value])){
                return response()->json([
                    'code' => 404,
                    'message' => $value. " is not set."
                ], 404);
            }
        }
        //endregion data validation

        // Generate a file name with extension
        $fileName = sessioned_hash(now()).'-'.$data['approver_id'].'.'.$file->getClientOriginalExtension();

        // Save the file
        $path = $file->storeAs('signatures', $fileName);

        $file_defination = $this->define(new Request([
            'full_path' => $path,
            'name' => 'approver_signature',
            'target_type' => 'approver',
            'target_id' => $data['approver_id'],
            'added_by' => $data['added_by'] ?? 0,
        ]));

        if(isset($file_defination) && !is_code_success( $file_defination->status() ) ){
            return response()->json([
                "code" => 500,
                "message" => "Failed to save file."
            ], 500);
        }

        $response = \Storage::get($path);
        $array = explode('.', $fileName);
        $extensions = end($array);
        $dataUri = 'data:image/'.  $extensions . ';base64,' . base64_encode($response);

        return response()->json([
            "code" => 200,
            "message" => "Successfully save signature.",
            "data" => [
                'signature' => $dataUri
            ]
        ], 200);

    }

}
