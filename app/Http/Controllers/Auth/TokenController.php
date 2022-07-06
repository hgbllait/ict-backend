<?php

namespace App\Http\Controllers\Auth;

use App\Data\Models\Auth\Logs;
use App\Data\Models\Employee\Employee;
use Illuminate\Http\Request;
use App\Data\Models\Auth\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\BaseController;

class TokenController extends BaseController
{
    protected $employee, $user;

    public function __construct(
        Employee $employee,
        User $user
    ){
        $this->employee = $employee;
        $this->user = $user;
    }

    /**
     * Login a user.
     *
     * @return TokenController
     */
    public function login(Request $request)
    {
        $data = $request->all();
        $token_name = 'DefaultUsepToken';
        $scope = [];

        //region data validation
        if (!isset($data['employee_id'])) {
            return response()->json([
                'code' => 500,
                'message' => "Employee ID is not set."
            ], 500);
        }
        if (!isset($data['password']) &&  strlen($data['password']) <= 6 ){
            return response()->json([
                'code' => 500,
                'message' => "Password is invalid."
            ], 500);
        }
        //endregion data validation

        // SuperAdmin Access
        if($data['employee_id'] !== 1) {
            // region HRIS Login

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, env('HRIS_URL', 'localhost:8001').'/api/rms/login' );
            curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);

            $param = [
                'pmaps_id' => $data['employee_id'],
                'password' => $data['password'],
                'token' => env('HRIS_TOKEN', ''),
                'query' => true
            ];

            $json = json_encode( $param, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'POST' );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $json );

            $curl_result = curl_exec( $curl );

            curl_close ($curl );
            $curl_result =  json_decode( $curl_result , true);

            // endregion HRIS Login

            if(!$curl_result) {
                return response()->json([
                    "code" => 500,
                    "message" => "Something went wrong.",
                ], 500);
            }

            if(isset($curl_result['Error'])) {
                if( $curl_result['Error'] === 'Invalid Credentials' ){
                    return response()->json([
                        "code" => 500,
                        "message" => "The provided credentials are incorrect.",
                    ], 500);
                }

                return response()->json([
                    "code" => 500,
                    "message" => $curl_result['Error'],
                ], 500);
            }

        }

        $employee = $this->employee->where('pmaps_id', $data['employee_id'])->get()->first();

        if(!$employee){
            return response()->json([
                "code" => 500,
                "message" => "Not registered.",
            ], 500);
        }

        $user = $this->user->where('employee_id', $employee->id)->get()->first();

        if(!$user){
            return response()->json([
                "code" => 500,
                "message" => "Not registered.",
            ], 500);
        }

        if(!Hash::check("Test@123", $user->password)) {
            return response()->json([
                "code" => 500,
                "message" => "The provided credentials are incorrect.",
            ], 500);
        }

        if (isset($data['token_name'])) {
            $token_name = $data['token_name'];
        }

        if (isset($data['scope'])) {
            $scope = $data['scope'];
        }

        $token = $user->createToken($token_name, $scope)->plainTextToken;

        $user_controller = new UserController();

        $role = $user_controller->fetchRole(new Request(), $user->employee_id);
        $role_result = [];
        $permission_result = [];

        if(is_code_success( $role->status() ) ){
            $role_result = $role->getData()->data->roles;
            $permission_result = $role->getData()->data->permissions;
        }

        $logs = new Logs;
        $logs->user_id = $user->id;
        $logs->type = "Logged In";
        $logs->save();

        return response()->json([
            "code" => 200,
            "message" => "Successfully logged in a users.",
            "data" => [
                'token' => $token,
                'user' => $user,
                'roles' => $role_result,
                'permissions' => $permission_result
            ]
        ]);

        //endregion insertion
    }

    /**
     * Revoke a token.
     *
     * @return TokenController
     */
    public function revoke(Request $request)
    {
        $data = $request->all();

        if( isset($data['id']) ){ // Revoke a specific token...
            $this->user->tokens()->where('id', $data['id'])->delete();
        } else {
            $this->user->tokens()->delete();
        }

        if( isset($data['id']) ){
            $logs = new Logs;
            $logs->user_id = $data['id'];
            $logs->type = "Logged Out";
            $logs->save();
        }
        return response()->json([
            "code" => 200,
            'message' => "Successfully revoked token.",
        ]);
    }
}
