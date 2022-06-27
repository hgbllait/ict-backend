<?php

namespace App\Http\Controllers\RBAC;

use App\Data\Models\Auth\User;
use App\Data\Models\RBAC\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

class PermissionController extends BaseController
{
    protected $meta_index = 'permission', $list_of_permission = ['read', 'write', 'delete'];

    /**
     * Fetch all permissions
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function all(Request $request)
    {
        $result = Permission::all();

        $permission_list = array_column($result->toArray(), 'name');

        $permission = [];
        $array_result = [];

        if( !empty($permission_list) ){

            foreach( $permission_list as $value ) {
                $result_array = explode('-', $value);
                $name = $result_array[0];

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

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved permission.",
            "data"       => [
                $this->meta_index   => $result,
                "adjust"            => $array_result,
                "count"             => $result->count(),
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
                'message' => "Permission name is not set."
            ], 500);
        }

        if(!isset($data['guard_name'])){
            $data['guard_name'] = 'web';
        }

        //endregion data validation

        $permission = new Permission();

        //region existence check

        if (isset($data['id'])) {
            $does_exist = $permission->find($data['id']);

            if (!$does_exist) {
                return response()->json([
                    'code'  => 500,
                    'message' => 'Permission ID does not exist.',
                ], 500);
            }

            $permission = $permission->find($data['id']);
        }

        if (isset($data['name'])){

            try {
                $does_exist = $permission->findByName($data['name'], 'web');
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $exception) {
                $does_exist = false;
            }

            if ($does_exist) {
                return response()->json([
                    'code'  => 500,
                    'message' => 'Permission name already exists.',
                    'parameters' => $data,
                ], 500);
            }
        }

        //endregion existence check

        //region insertion

        if(!$permission->fill($data)->save()){
            return response()->json([
                "code" => 500,
                "message" => "Data Validation Error.",
                "description" => "An error was detected on one of the inputted data.",
            ], 500);
        }

        return response()->json([
            "code" => 200,
            "message" => "Successfully defined a permission.",
            "parameters" => $permission,
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

        $permission = Permission::find($data['id']);

        if(!$permission){
            return response()->json([
                "code" => 404,
                "message" => "No permission found.",
                "parameters" => $data
            ], 404);
        }

        $deleted_already = $permission->withTrashed()->where('id', $data['id'])->first();

        if( !$deleted_already ){
            return response()->json([
                "code" => 404,
                'message' => "This permission does not exists",
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

        if(!$permission->delete()){
            return response()->json([
                "code" => 500,
                "message" => "Deleting permission was not successful.",
                "parameters" => $data
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Permission was successfully deleted,",
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

        $result = Permission::where('id', $id)->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "No permission found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved permission.",
            "data"       => [
                $this->meta_index => $result,
                "count"     => $result->count(),
                "parameters" => $data
            ]
        ]);
    }

    public function fetchUser(Request $request)
    {
        $data = $request->all();

        if(!isset($data['name'])){
            return response()->json([
                'code' => 500,
                'message' => "Permission name is not set."
            ], 500);
        }

        $result = User::whereHas("roles", function($q) use ($data) {
            $q->whereHas('permissions', function($q1) use ($data) {
                $q1->where('name', '=', $data['name']);
            });
        })->get();

        if ($result->count() < 1) {
            return response()->json([
                "code"       => 404,
                "message"      => "No users found",
                "data"       => [
                    $this->meta_index => $result,
                ],
                "parameters" => $data,
            ], 404);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved user by permission.",
            "data"       => [
                "user" => $result,
                "count"     => $result->count(),
                "parameters" => $data
            ]
        ]);
    }

}
