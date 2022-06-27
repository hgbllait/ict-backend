<?php

namespace App\Http\Controllers\Definition\Equipment;

use DB;
use App\Data\Models\Definition\Equipment\Equipment;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

class EquipmentController extends BaseController
{
    protected
        $equipment, $meta_index = 'equipment';

    function __construct(
        Equipment $equipment
    ){
        $this->equipment = $equipment;
        // middleware
    }

    public function all()
    {

        $result = Equipment::orderBy('id', 'DESC')
            ->get();

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved user.",
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

        $result = Equipment::where('id', $id)->first();

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved equipment.",
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
        foreach( ['alias',
                     'equipment',
                     'serial_no',
                     'brand',
                     'location'
                 ] as $value) {
            if( !isset( $data[$value] ) ) {
                return response()->json([
                    'code'  => 404,
                    'message' => $value .' is not set.',
                ], 404);
            }
        }

        $equipment = $this->makeModel ( $data, $this->equipment );

        $message = 'Successfully created equipment.';

        //region insertion

        //region existence check
        if (isset($data['id'])) {
            $equipment = $this->equipment->find($data['id']);
            $message = 'Successfully updated equipment.';

            if (!$equipment) {
                return response()->json([
                    'code'  => 404,
                    'message' => 'Equipment ID does not exist.',
                ], 404);
            }
        }
        //endregion existence check

        if(!$equipment->save($data)){
            return response()->json([
                "code" => 500,
                "message" => "Data Validation Error.",
                "description" => "An error was detected on one of the inputted data.",
                "data" =>   [
                    "errors" => $equipment->errors(),
                ]
            ], 500);
        }

        return response()->json([
            "code" => 200,
            "message" => $message,
            "data" => [
                $this->meta_index => $equipment,
            ]
        ]);
        //endregion insertion

    }

    public function delete( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;
        $record = Equipment::find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No equipment found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This equipment does not exists",
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
                "message" => "Deleting equipment was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Equipment was successfully deleted,",
            "parameters" => $data
        ]);

    }

}
