<?php

namespace App\Http\Controllers\Auth;

use App\Data\Models\Auth\Logs;
use Illuminate\Http\Request;
use App\Data\Models\Auth\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\BaseController;

class TokenController extends BaseController
{
    protected $user;

    public function __construct(
        User $user
    ){
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
        if (!isset($data['username'])) {
            return response()->json([
                'code' => 500,
                'message' => "Username is not set."
            ], 500);
        }
        if (!isset($data['password']) &&  strlen($data['password']) <= 6 ){
            return response()->json([
                'code' => 500,
                'message' => "Password is invalid."
            ], 500);
        }
        //endregion data validation

        $user = $this->user->where('username', $data['username'])->get()->first();

        if(!$user || !Hash::check($data['password'], $user->password)) {
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
