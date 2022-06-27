<?php

namespace App\Http\Controllers\Auth;


use App\Data\Models\Utilities\Files\File;
use DB;
use App\Data\Models\Auth\User;
use Illuminate\Http\Request;
use App\Data\Models\RBAC\Permission;
use App\Data\Models\RBAC\Role;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\BaseController;

class UserController extends BaseController
{
    function __construct()
    {
        // middleware
    }

    protected $meta_index = 'user', $response_variable = 'role';

    public function all()
    {

        $result = User::with('employee')
            ->with('logs')
            ->orderBy('id', 'DESC')
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

        $result = User::with('employee')
            ->with('logs')
            ->where('id', $id)->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "No user are found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved user.",
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

        if(!isset($data['email'])){
            return response()->json([
                'code' => 404,
                'message' => "Email is not set."
            ], 404);
        }
        if(!isset($data['username'])){
            return response()->json([
                'code' => 404,
                'message' => "User is not set."
            ], 404);
        }
        if(!isset($data['password'])){
            return response()->json([
                'code' => 404,
                'message' => "Password is not set."
            ], 404);
        }

        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }

        //endregion data validation

        $user = new User();

        //region existence check

        if (isset($data['id'])) {
            $user = $user->find($data['id']);

            // region Existence Check
            if (!$user) {
                return response()->json([
                    'code'  => 500,
                    'message' => 'User ID does not exist.',
                ], 500);
            }
            // endregion Existence Check

            if(isset($data['email'])){
                if($user->email == $data['email']){
                    unset($data['email']);
                }
            }

            if(isset($data['username'])){
                if($user->username == $data['username']){
                    unset($data['username']);
                }
            }
            $execute = $user->update($data);

            $message = 'Successfully updated user.';
        } else {

            // region Check if exists
            $email_check = $user->where('email', $data['email'])->get()->first();
            $username_check = $user->where('username', $data['username'])->get()->first();

            if(isset($email_check)){
                return response()->json([
                    'code' => 500,
                    'message' => "Email is already taken."
                ], 500);
            }
            if(isset($username_check)){
                return response()->json([
                    'code' => 500,
                    'message' => "Username is already taken."
                ], 500);
            }
            // endregion Check if exists

            $execute = $user->save($data);

            $message = 'Successfully created user.';
        }

        //endregion existence check

        if(!$execute){
            return response()->json([
                "code" => 500,
                "message" => "Data Validation Error.",
                "description" => "An error was detected on one of the inputted data.",
                "data" =>   [
                    "errors" => $execute->errors(),
                ]
            ], 500);
        }

        return response()->json([
            "code" => 200,
            "message" => $message,
        ]);
        //endregion insertion

    }

    public function delete( Request $request, $id )
    {
        $data = $request->all();
        $data['id'] = $id;
        $record = User::find($data['id']);

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No users found.",
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
                "message" => "Deleting user was not successful.",
                "parameters" => $data
            ], 500);
        }

        $record->status = "inactive";

        if(!$record->save()){
            return response()->json([
                "code"       => 500,
                "message"      => "Changing user status was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "User was successfully deleted,",
            "parameters" => $data
        ]);

    }

    public function register(Request $request)
    {
        $data = $request->all();
        $user = new User();

        // region Validation

        $fillable = [
            'email' => 'Email',
            'username' => 'Username',
            'password' => 'Password',
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'address' => 'Address',
            'contact_number' => 'Contact number'
        ];
        foreach($fillable as $key => $value) {
            if (!isset($data[$key])) {
                return response()->json([
                    'code' => 500,
                    'message' => $value." is not set."
                ], 500);
            }
        }
        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }

        if( strlen($data['password']) <= 6 ){
            return response()->json([
                'code' => 500,
                'message' => "Password is invalid."
            ], 500);
        }

        // endregion Validation

        // region Existence Check
        if($user->where('email', $data['email'])->exists()){
            return response()->json([
                'code' => 500,
                'message' => "Email is already taken."
            ], 500);
        }
        if($user->where('username', $data['username'])->exists()){
            return response()->json([
                'code' => 500,
                'message' => "Username is already taken."
            ], 500);
        }

        // endregion Existence Check

        $employee_controller = new EmployeeController();

        // Create Employee
        $employee = $employee_controller->define($request);

        if(isset($employee) && !is_code_success( $employee->status() ) ){
             return response()->json([
                "code" => 500,
                "message" => "Data Validation Error.",
                "description" => "An error was detected on one of the inputted data.",
                "data" => $employee->getData()
            ], 500);
        }

        $data['employee_id'] = $employee->getData()->data->employee->id;

        if(!$user->save($data)){
             return response()->json([
                "code" => 500,
                "message" => "Data Validation Error.",
                "description" => "An error was detected on one of the inputted data.",
                "data" =>   [
                    "errors" => $user->errors(),
                ]
            ], 500);
        }

         return response()->json([
            "code" => 200,
            "message" => "Successfully added a user.",
            'messages' => [
                $this->meta_index => $user,
            ],
        ]);

    }

    public function assignRole(Request $request, $id)
    {

        $data = $request->all();
        $data['id'] = $id;

        if( !isset($data['id']) ||
            !is_numeric ( $data['id'] ) ||
            $data['id'] <= 0 ){
             return response()->json([
                'code' => 500,
                'message' => "ID is not set."
            ], 500);
        }

        if( !isset($data['name']) ){
             return response()->json([
                'code' => 500,
                'message' => "Name is not set."
            ], 500);
        }

        if( !isset($data['guard_name'])){
            $data['guard_name'] = 'web';
        }

        if($data['guard_name'] != 'web'){
            $this->response_variable = $data['guard_name'];
        }

        $record = User::find($data['id']);

        if(!$record){
             return response()->json([
                "code" => 404,
                "message" => "No record was found.",
                "description" => "No record was found based on the key.",
                "parameters" =>  [
                    "id" => $data['id'],
                    "role" => $data['name'],
                ],
            ], 404);
        }

        try {
            $role_existence = Role::findByName($data['name'], $data['guard_name']);
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $exception) {
             return response()->json([
                "code" => 404,
                "message" => "No ". $this->response_variable ." was found.",
                "description" => "No ". $this->response_variable ." was found based on the key.",
                "parameters" =>  [
                    "id" => $data['id'],
                    $this->response_variable => $data['name']
                ],
            ], 404);
        }

        //set guard name
        $record->guard_name = $data['guard_name'];

        if(!$record->assignRole($data['name'])){
             return response()->json([
                "code" => 500,
                "message" => "Data error.",
                "description" => "Could not assign ". $this->response_variable ." to user.",
                "parameters" =>  [
                    "id" => $data['id'],
                    $this->response_variable => $data['name'],
                ],
            ], 500);
        }

         return response()->json([
            "code" => 200,
            "message" => "Successfully assigned to ". $this->response_variable ."!",
            "data" => $record,
        ]);

    }

    public function fetchPermission(Request $request, $id)
    {
        $data = $request->all();
        $data['id'] = $id;
        if( !isset($data['id']) ||
            !is_numeric ( $data['id'] ) ||
            $data['id'] <= 0 ){
             return response()->json([
                'code' => 500,
                'message' => "ID is not set."
            ]);
        }

        $record = User::find($data['id']);

        if(!$record){
             return response()->json([
                "code" => 404,
                "message" => "No record was found.",
                "description" => "No record was found based on the key.",
                "parameters" =>  [
                    "id" => $data['id'],
                ],
            ]);
        }

        $record->getRoleNames();
        $record->getAllPermissions();

        foreach($record->roles as $role){
            $roles[] = $role->name;

            foreach($role->permissions as $permission){
                $permissions[] = $permission->name;
                $record_permissions[] = $permission;
            }
        }

        foreach($record->permissions as $permission){
            $permissions[] = $permission->name;
            $record_permissions[] = $permission;
        }

        if(!isset($permissions)){
            $return_data = [
                "user" => $record->username,
                "email" => $record->email,
                "permissions" => [],
                "hash" => md5($record->roles . $record->permissions),
            ];

             return response()->json([
                "code" => 404,
                "message" => "No permission was found.",
                "description" => "Cannot retrieve user's current permissions",
                "parameters" => $return_data,
            ]);
        }

         return response()->json([
            "code" => 200,
            "message" => "Successfully retrieved current permissions!",
            "data" => [
                "user" => $record->username,
                "email" => $record->email,
                "permissions" => $record_permissions,
                "hash" => md5($record->roles . $record->permissions),
            ],
        ]);

    }

    public function fetchRole(Request $request, $id)
    {

        $data = $request->all();
        $data['id'] = $id;

        $user = new User();

        if( !isset($data['employee_id']) && (!isset($data['id']) ||
                !is_numeric ( $data['id'] ) ||
                $data['id'] <= 0 )){
             return response()->json([
                'code' => 500,
                'message' => "ID is not set."
            ]);
        }

        if(isset($data['employee_id']) && (!isset($data['id']) || $data['id'] <= 0)){
            $record = $user->where('employee_id', $data['employee_id'])->first();
        }else{
            $record = $user->find($data['id']);
        }

        if(!$record){
             return response()->json([
                "code" => 404,
                "message" => "User not found.",
                "description" => "No record was found based on the key.",
            ], 404);
        }

        $record->getRoleNames();
        $record->getAllPermissions();
        if(!$record->roles || $record->roles->isEmpty()){
             return response()->json([
                "code" => 404,
                "message" => "No role was found.",
                "description" => "Cannot retrieve user's current role",
            ], 404);
        }

        $roles = [];
        $permissions = [];

        foreach($record->roles as $role){
            if($role->guard_name == 'web'){
                $roles[] = $role->name;
            }

            foreach($role->permissions as $permission){
                $permissions[] = $permission->name;
            }
        }

        foreach($record->permissions as $permission){
            $permissions[] = $permission->name;
        }

         return response()->json([
            "code" => 200,
            "message" => "Successfully retrieved current role!",
            "data" => [
                "user" => $record->username,
                "email" => $record->email,
                "roles" => $roles,
                "permissions" => $permissions,
                "hash" => md5($record->roles . $record->permissions),
            ]
        ]);

    }

    public function fetchPersonal(Request $request, $id)
    {

        $data = $request->all();
        $data['id'] = $id;
        if( !isset($data['id']) ||
            !is_numeric ( $data['id'] ) ||
            $data['id'] <= 0 ){
            return response()->json([
                'code' => 500,
                'message' => "ID is not set."
            ]);
        }

        $record = User::with('employee')
            ->with('approver')
            ->with('logs')
            ->where('id', $id)->first();

        if(!$record){
            return response()->json([
                "code" => 404,
                "message" => "No record was found.",
                "description" => "No record was found based on the key.",
                "parameters" =>  [
                    "id" => $data['id'],
                ],
            ]);
        }
        $signature = null;
        if(isset($record['approver'])){
            $signature = File::where('target_type', 'approver')
                ->where('name', 'approver_signature')
                ->where('target_id', $record->approver->approver_id)->first();

            if($signature && isset($signature->toArray()['full_path'])){
                $response = \Storage::get($signature->toArray()['full_path']);
                $array = explode('.', $signature['full_path']);
                $extensions = end($array);
                $signature = 'data:image/'.  $extensions . ';base64,' . base64_encode($response);
            }
        }


        return response()->json([
            "code" => 200,
            "message" => "Successfully retrieved personal information.",
            "data" => [
                "user" => $record,
                "signature" => $signature
            ],
        ]);

    }

    public function managePermission(Request $request )
    {
        $data = $request->all();

        if( !isset($data['id']) ){
             return response()->json([
                'code' => 500,
                'message' => "User ID is not set."
            ], 500);
        }

        if( !isset($data['name']) ){
             return response()->json([
                'code' => 500,
                'message' => "Permission is not set."
            ], 500);
        }


        /**
         * @var User $record
         */
        $record = User::find($data['id']);

        if( $record === null ){
            $record = User::with('permissions')->where( 'username', $data['id'] )->get()->first();
        }

        if(!$record){
             return response()->json([
                "code" => 404,
                "message" => "No record was found.",
                "description" => "No record was found based on the key.",
                "parameters" =>  [
                    "id" => $data['id'],
                    "permission" => $data['name']
                ],
            ], 404);
        }

        try {
            $permission_existence = Permission::findByName($data['name'], 'web');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $exception) {
             return response()->json([
                "code" => 404,
                "message" => "No permission was found.",
                "description" => "No permission was found based on the key.",
                "parameters" =>  [
                    "id" => $data['id'],
                    "permission" => $data['name']
                ],
            ], 404);
        }

        $message = "Successfully managed permission.";

        switch( $data['type'] ){
            case "remove":
                $message = "Successfully removed permission.";

                if( $record->hasPermissionTo( $data['name'] ) ){
                    if(!$record->revokePermissionTo( $data['name'] )){
                         return response()->json([
                            "code" => 500,
                            "message" => "Data error.",
                            "description" => "Could not remove permission of user.",
                            "parameters" =>  [
                                "id" => $data['id'],
                                "permission" => $data['name'],
                            ],
                        ], 500);
                    }
                }
                break;
            case "allow":
                if(!$record->givePermissionTo($data['name'])){
                     return response()->json([
                        "code" => 500,
                        "message" => "Data error.",
                        "description" => "Could not give permission to user.",
                        "parameters" =>  [
                            "id" => $data['id'],
                            "permission" => $data['name']
                        ],
                    ], 500);
                }
                break;
            default:
                 return response()->json([
                    "code" => 500,
                    "message" => _("Invalid type."),
                    "description" => _("Invalid type provided"),
                    "parameters" =>  [
                        "type" => $data['type'],
                    ],
                ], 500);
        }

         return response()->json([
            "code" => 200,
            "message" => _( $message ),
            "data" => $record,
            "parameters" => [
                'permission' => $data['name'],
            ]
        ]);
    }

    public function assignPermission(Request $request )
    {
        $data = $request->all();

        if( !isset($data['id']) ){
             return response()->json([
                'code' => 500,
                'message' => "ID is not set."
            ], 500);
        }

        if( !isset($data['name']) ){
             return response()->json([
                'code' => 500,
                'message' => "Permission is not set."
            ], 500);
        }

        /**
         * @var User $rec
         */
        $record = User::find($data['id']);

        if(!$record){
             return response()->json([
                "code" => 404,
                "message" => "No record was found.",
                "description" => "No record was found based on the key.",
                "parameters" =>  [
                    "id" => $data['id'],
                    "permission" => $data['name']
                ],
            ], 404);
        }

        try {
            $permission_existence = Permission::findByName($data['name'], 'web');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $exception) {
             return response()->json([
                "code" => 404,
                "message" => "No permission was found.",
                "description" => "No permission was found based on the key.",
                "parameters" =>  [
                    "id" => $data['id'],
                    "permission" => $data['name']
                ],
            ], 404);
        }

        if(!$record->givePermissionTo($data['name'])){
             return response()->json([
                "code" => 500,
                "message" => "Data error.",
                "description" => "Could not give permission to user.",
                "parameters" =>  [
                    "id" => $data['id'],
                    "permission" => $data['name']
                ],
            ], 500);
        }

         return response()->json([
            "code" => 200,
            "message" => "Successfully assigned permission!",
            "data" => $record,
            "parameters" => [
                'permission' => $data['name'],
            ]
        ]);

    }

    public function removeRole(Request $request, $id)
    {
        $data = $request->all();
        $data['id'] = $id;

        if( !isset($data['id']) ||
            !is_numeric ( $data['id'] ) ||
            $data['id'] <= 0 ){
             return response()->json([
                'code' => 500,
                'message' => "ID is not set."
            ], 500);
        }

        if(!isset($data['name'])){
             return response()->json([
                'code' => 500,
                'message' => "Role name is not set."
            ], 500);
        }

        if( !isset($data['guard_name'])){
            $data['guard_name'] = 'web';
        }

        if($data['guard_name'] != 'web'){
            $this->response_variable = $data['guard_name'];
        }

        $record = User::find($data['id']);

        if(!$record){
             return response()->json([
                "code" => 404,
                "message" => "No record was found.",
                "description" => "No record was found based on the key.",
                "parameters" =>  [
                    "id" => $data['id'],
                    $this->response_variable => $data['name'],
                ],
            ], 404);
        }

        try {
            $role_existence = Role::findByName($data['name'], $data['guard_name']);
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $exception) {
             return response()->json([
                "code" => 404,
                "message" => "No ". $this->response_variable ." was found.",
                "description" => "No ". $this->response_variable ." was found based on the key.",
                "parameters" =>  [
                    "id" => $data['id'],
                    $this->response_variable => $data['name']
                ],
            ], 404);
        }

        //set guard name
        $record->guard_name = $data['guard_name'];
        $record->removeRole($data['name']);

         return response()->json([
            "code" => 200,
            "message" => "Successfully removed from ". $this->response_variable ."!",
            "data" => $record,
            "parameters" => [
                $this->response_variable => $data['name'],
            ],
        ]);

    }

    public function revokePermission(Request $request, $id)
    {
        $data = $request->all();
        $data['id'] = $id;

        if( !isset($data['id']) ||
            !is_numeric ( $data['id'] ) ||
            $data['id'] <= 0 ){
             return response()->json([
                'code' => 500,
                'message' => "ID is not set."
            ], 500);
        }

        $record = User::find($data['id']);

        if(!$record){
             return response()->json([
                "code" => 404,
                "message" => "No record was found.",
                "description" => "No record was found based on the key.",
                "parameters" =>  [
                    "id" => $data['id'],
                    "permission" => $data['name'],
                ],
            ], 404);
        }

        try {
            $permission_existence = Permission::findByName($data['name'], 'web');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $exception) {
             return response()->json([
                "code" => 404,
                "message" => "No permission was found.",
                "description" => "No permission was found based on the key.",
                "parameters" =>  [
                    "id" => $data['id'],
                    "permission" => $data['name']
                ],
            ], 404);
        }


        if(!$record->revokePermissionTo($data['name'])){
             return response()->json([
                "code" => 500,
                "message" => "Data error.",
                "description" => "Could not revoke permission.",
                "data" =>   [
                    "errors" => $record->errors(),
                ]
            ], 500);
        }

        return response()->json([
            "code" => 200,
            "message" => "Successfully revoked permission!",
            "data" => $record,
        ]);

    }

}
