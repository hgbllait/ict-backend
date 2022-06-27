<?php
// region Rbac
Route::group([
    "namespace" => "RBAC",
//    'middleware' => 'auth:sanctum'
], function( ) {

    Route::get ( "/", function () {
        return Response::respond ( [
            "code" => 404,
            "title" => "Default endpoint not found."
        ] );
    } );

    Route::group([
        "prefix" => "permissions"
    ], function( ) {

        Route::get ("all", "PermissionController@all");
        Route::post("create", "PermissionController@create");
        Route::get("fetch/user", "PermissionController@fetchUser");
        Route::get("fetch/{permission_id}", "PermissionController@fetch");
        Route::post('update/{permission_id}', 'PermissionController@update');
        Route::post('delete/{permission_id}', 'PermissionController@delete');
        Route::get('search', 'PermissionController@search');
    });

    Route::group([
        "prefix" => "roles"
    ], function( ) {

        Route::get ("all", "RoleController@all");
        Route::post("create", "RoleController@create");
        Route::get('fetch/permission/', 'RoleController@fetchPermission');
        Route::get("fetch/{role_id}", "RoleController@fetch");
        Route::post('update/{role_id}', 'RoleController@update');
        Route::post('delete/{role_id}', 'RoleController@delete');
        Route::post('give/permission/{role_id}', 'RoleController@givePermission');
        Route::post('revoke/permission/{role_id}', 'RoleController@revokePermission');
        Route::get('fetch/permission/{role_id}', 'RoleController@fetchPermission');
        Route::get('users', 'RoleController@fetchUsersByRole');
        Route::get('search', 'RoleController@search');
    });

});

// endregion Rbac
