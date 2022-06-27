<?php

namespace App\Http\Controllers\RBAC;

use App\Data\Models\Auth\User;
use App\Data\Models\RBAC\Permission;
use App\Data\Models\RBAC\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

class RoleController extends BaseController
{
    protected $meta_index = 'role', $list_of_permission = ['read', 'write', 'delete'];

    /**
     * Fetch all permissions
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function all(Request $request)
    {
        $result = Role::all();

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved permission.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
            ]
        ]);
    }

    /**
     * Create a permission
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function define(Request $request)
    {
        $data = $request->all();

        //region data validation

        if(!isset($data['name'])){
            return response()->json([
                'code' => 500,
                'message' => "Role name is not set."
            ], 500);
        }

        if(!isset($data['guard_name'])){
            $data['guard_name'] = 'web';
        }

        if ($data['guard_name'] != 'web') {
            $this->meta_index = $data['guard_name'];
        }

        //endregion data validation

        $role = new Role();

        //region existence check

        if (isset($data['id'])) {
            $does_exist = $role->find($data['id']);

            if (!$does_exist) {
                return response()->json([
                    'code'  => 500,
                    'message' => 'Role ID does not exist.',
                ], 500);
            }

        }

        if (isset($data['name'])){

            try {
                $does_exist = $role->findByName($data['name'], 'web');
            } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $exception) {
                $does_exist = false;
            }

            if ($does_exist) {
                return response()->json([
                    'code'  => 500,
                    'message' => 'Role name already exists.',
                    'parameters' => $data,
                ], 500);
            }
        }

        if (isset($data['id'])) {
            $role = $role->find($data['id']);
        }

        //endregion existence check

        //region insertion

        if(!$role->fill($data)->save()){
            return response()->json([
                "code" => 500,
                "message" => "Data Validation Error.",
                "description" => "An error was detected on one of the inputted data.",
            ], 500);
        }

        return response()->json([
            "code" => 200,
            "message" => "Successfully defined a role.",
            "parameters" => $role,
        ]);

        //endregion insertion
    }

    /**
     * Delete a permission
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, $id)
    {
        $data = $request->all();
        $data['id'] = $id;

        $role = Role::find($data['id']);

        if(!$role){
            return response()->json([
                "code" => 404,
                "message" => "No role found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $role->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This role does not exists",
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

        if(!$role->delete()){
            return response()->json([
                "code" => 500,
                "message" => "Deleting role was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Role was successfully deleted,",
            "parameters" => $data
        ]);
    }

    /**
     * Fetch a permission by id
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function fetch(Request $request, $id)
    {
        $data = $request->all();
        $data['id'] = $id;

        $result = Role::where('id', $data['id'])->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "No role found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved role.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
                "parameters" => $data
            ]
        ]);

    }

    /**
     * Fetch permissions of a role
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function fetchPermission(Request $request)
    {
        $data = $request->all();
        if(isset($data['id'])){
            $result = Role::where('id', $data['id'])->get();
            $result->getAllPermissions();
            $result['adjust'] = $this->adjustPermission($result->toArray()['permissions']);
        } else {
            $result = Role::all();
            foreach ($result as $role) {
                $role->getAllPermissions();
                $role['adjust'] = $this->adjustPermission($role->toArray()['permissions']);
            }
        }

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "No role found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved role.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
                "parameters" => $data
            ]
        ]);

    }

    /**
     * Fetch all users by role
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function fetchUsersByRole(Request $request)
    {
        $data = $request->all();

        if (!isset($data['key'])) {
            return response()->json([
                'code' => 500,
                'message' => "Role key is not set.",
            ], 500);
        }

        if (!isset($data['guard_name'])) {
            $data['guard_name'] = 'web';
        }

        if (is_numeric($data['key'])) {
            $record = Role::find($data['key']);
        } else {
            try {
                $record = Role::findByName($data['key'], $data['guard_name']);
            } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $exception) {
                $record = false;
            }
        }

        if (!$record) {
            return response()->json([
                "code" => 404,
                "message" => "No record was found.",
                "description" => "There is no role with the key " . $data['key'],
                "parameters" => [
                    "key" => $data['key'],
                ],
            ], 404);
        }

        //set guard name before assigning model to $users
        $user = new User();
        $user->guard_name = $data['guard_name'];
        //query users based on guard name
        try {
            $users = $user->role($data['key']);
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $exception) {
            $users = false;
        }

        if (!$users) {
            return response()->json([
                "code" => 404,
                "message" => "No record was found.",
                "description" => "No record was found based on the key.",
                "parameters" => [
                    "key" => $data['key'],
                ],
            ], 404);
        }

        $users_count = $users;
        $count = $users_count->get()->count();
        $users = $users->get()->all();

        if ($data['guard_name'] != 'web') {
            $this->meta_index = $data['guard_name'];
        }

        return response()->json([
            "code" => 200,
            "message" => "Successfully retrieved users under the current " . $this->meta_index . "!",
            "data" => [
                $this->meta_index => $data['key'],
                'users' => $users,
                'count' => $count,
            ],
        ]);
    }

    /**
     * Give permissions to a role
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function givePermission(Request $request, $id)
    {
        $data = $request->all();
        $data['id'] = $id;

        if (!isset($data['id']) ||
            !is_numeric($data['id']) ||
            $data['id'] <= 0) {
            return response()->json([
                'code' => 500,
                'message' => "ID is not set.",
            ], 500);
        }

        if (!isset($data['name'])) {
            return response()->json([
                'code' => 500,
                'message' => "Permission name is not set.",
            ], 500);
        }

        $record = Role::find($data['id']);

        if (!$record) {
            return response()->json([
                "code" => 404,
                "message" => "No record was found.",
                "description" => "No record was found based on the key.",
                "parameters" => [
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
                "parameters" => [
                    "id" => $data['id'],
                    "permission" => $data['name'],
                ],
            ], 404);
        }

        if (!$record->givePermissionTo($data['name'])) {
            return response()->json([
                "code" => 500,
                "message" => "Data error.",
                "description" => "Could not give permission to user.",
                "parameters" => [
                    "id" => $data['id'],
                    "permission" => $data['name'],
                ],
            ], 500);
        }

        return $this->setResponse([
            "code" => 200,
            "message" => "Successfully assigned permission.",
            "data" => $record,
            "parameters" => [
                "name" => $data['name'],
            ],
        ]);
    }

    /**
     * Revoke permissions of a role
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function revokePermission(Request $request, $id)
    {
        $data = $request->all();
        $data['id'] = $id;

        if (!isset($data['id']) ||
            !is_numeric($data['id']) ||
            $data['id'] <= 0) {
            return response()->json([
                'code' => 500,
                'message' => "ID is not set.",
            ], 500);
        }

        if (!isset($data['name'])) {
            return response()->json([
                'code' => 500,
                'message' => "Permission name is not set.",
            ], 500);
        }

        $record = Role::find($data['id']);

        if (!$record) {
            return response()->json([
                "code" => 404,
                "message" => "No record was found.",
                "description" => "No record was found based on the key.",
                "parameters" => [
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
                "parameters" => [
                    "id" => $data['id'],
                    "permission" => $data['name'],
                ],
            ], 404);
        }

        if (!$record->revokePermissionTo($data['name'])) {
            return response()->json([
                "code" => 500,
                "message" => "Data error.",
                "description" => "Could not revoke permission.",
                "data" => [
                    "errors" => $record->errors(),
                ],
            ], 500);
        }

        return response()->json([
            "code" => 200,
            "message" => "Successfully revoked permission.",
            "data" => $record,
            "parameters" => [
                "name" => $data['name'],
            ],
        ]);
    }

    private function adjustPermission( $array=[] ){
        // region Operation Adjust Permission
        $permission_controller = new PermissionController();

        $permission_result = $permission_controller->all(new Request());

        if(is_code_success( $permission_result->status() ) ){
            $permission_result = json_decode(json_encode($permission_result->getData()->data->adjust), true);
        } else {
            $permission_result = [];
        }
        array_walk_recursive($permission_result, function (&$value, $key) {
            if ($value === true) {
                $value = false;
            }
        });
        $permission_list = array_column($array, 'name');

        $permission = [];
        $array_result = [];

        if( !empty($permission_list) ){

            foreach( $permission_list as $value ) {
                $result_array = explode('-', $value);

                foreach ($result_array as $value_) {
                    if (in_array($value_, $this->list_of_permission)) {
                        $permission_name = $value_;
                        if (($key = array_search($value_, $result_array)) !== false) {
                            unset($result_array[$key]);
                        }
                    }

                }

                $name = implode(' ', array_map("ucfirst", $result_array));

                $permission[$name][] = $permission_name;
            }

            foreach($permission as $key => $value){
                $list_of_permission = [];
                foreach($this->list_of_permission as $value_){
                    $list_of_permission[$value_] = false;
                    if (in_array($value_, $value)) {
                        $list_of_permission[$value_] = true;
                    }
                }
                $name = [
                    'name' => $key
                ];
                $array_result[] = array_merge( $name, $list_of_permission );
            }

        }

        $array_merge = array_merge($permission_result,$array_result);
        $role_result = [];
        foreach($array_merge as $value){
            $role_result[$value['name']] = $value;
        }

        return $role_result;
    }

}
