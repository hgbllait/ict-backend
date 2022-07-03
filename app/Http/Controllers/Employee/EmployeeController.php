<?php

namespace App\Http\Controllers\Employee;

use DB;
use App\Data\Models\Employee\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

class EmployeeController extends BaseController
{
    function __construct()
    {
        // middleware
    }

    protected $meta_index = 'employee';

    public function all()
    {

        $result = Employee::all();

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved employee.",
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

        $result = Employee::where('id', $id)->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "No employees are found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved employee.",
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
        if(!isset($data['id'])){

            if(!isset($data['first_name'])){
                return response()->json([
                    'code' => 404,
                    'message' => "First Name is not set."
                ], 404);
            }
            if(!isset($data['last_name'])){
                return response()->json([
                    'code' => 404,
                    'message' => "Last name is not set."
                ], 404);
            }
            if(!isset($data['address'])){
                return response()->json([
                    'code' => 404,
                    'message' => "Address is not set."
                ], 404);
            }
            if(!isset($data['email'])){
                return response()->json([
                    'code' => 404,
                    'message' => "Email is not set."
                ], 404);
            }
            if(!isset($data['contact_number'])){
                return response()->json([
                    'code' => 404,
                    'message' => "Contact number is not set."
                ], 404);
            }
            if(!isset($data['position'])){
                return response()->json([
                    'code' => 404,
                    'message' => "Position is not set."
                ], 404);
            }
            if(!isset($data['full_name'])){
                $data['full_name'] = $data['first_name'] . ' ' . $data['last_name'];
            }

        }

        if(isset($data['image_url'])){
            $data['image'] = $data['image_url'];
        }
        //endregion data validation

        $employee = new Employee();

        //region existence check

        if (isset($data['id'])) {
            $does_exist = $employee->find($data['id']);

            if (!$does_exist) {
                return response()->json([
                    'code'  => 404,
                    'message' => 'Employee ID does not exist.',
                ], 404);
            }
        }
        //endregion existence check

        $message = 'Successfully created employee.';

        //region insertion
        if (isset($data['id'])) {
            $employee = $employee->find($data['id']);

            if(isset($data['email'])){
                if($employee->email == $data['email']){
                    unset($data['email']);
                }
            }

            $message = 'Successfully updated employee.';

        }

        if(isset($data['first_name']) && isset($data['last_name'])){
            $data['full_name'] = $data['first_name'] . ' ' . $data['last_name'];
        }else if(isset($data['first_name']) && !isset($data['last_name'])){
            $data['full_name'] = $data['first_name'] . ' ' . $employee->last_name;
        }else if(!isset($data['first_name']) && isset($data['last_name'])){
            $data['full_name'] = $employee->first_name . ' ' . $data['last_name'];
        }

        if(!$employee->save($data)){
            return response()->json([
                "code" => 500,
                "message" => "Data Validation Error.",
                "description" => "An error was detected on one of the inputted data.",
                "data" =>   [
                    "errors" => $employee->errors(),
                ]
            ], 500);
        }

        return response()->json([
            "code" => 200,
            "message" => $message,
            "data" => [
                $this->meta_index => $employee,
            ]
        ]);
        //endregion insertion

    }

    public function delete( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;
        $record = Employee::find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No employee found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $record->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This user does not exists",
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
                "message" => "Deleting employee was not successful.",
                "parameters" => $data
            ], 500);
        }

        $record->status = "inactive";

        if(!$record->save()){
            return response()->json([
                "code"       => 500,
                "message"      => "Changing employee status was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Employee was successfully deleted,",
            "parameters" => $data
        ]);

    }

}
