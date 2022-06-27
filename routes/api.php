<?php

use App\Data\Models\Auth\Logs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group([
    "prefix" => "v1",
], function(){
    Route::get("/", function(){
        return response()->json([
            "code" => 401,
            "message" => "Unauthorized access."
        ], 401);
    });

    Route::group([
        'prefix' => '/',
        'namespace' => 'Auth',
    ], function () {
        Route::post('login', "TokenController@login");
        Route::group([
            'prefix' => 'auth',
        ], function () {
            Route::post('login', "TokenController@login");
            Route::post("register", "UserController@register"); // Create Employee and user

            Route::group([
                'middleware' => 'auth:sanctum'
            ], function () {
                Route::get('user', function (Request $request) {

                    if ($request->user() === null) {
                        return response()->json([
                            "code" => 401,
                            "message" => "You are not logged in"
                        ], 401);
                    }

                    $user = $request->user();
                    $role = [];

                    if (isset($user->id)) {
                        $fetch_role = app('App\Http\Controllers\Auth\UserController')->fetchRole($request, $user->id)->getData();
                    }

                    if (isset($fetch_role->meta->roles)) {
                        $role = $fetch_role->meta->roles;
                    }

                    return response()->json([
                        "code" => 200,
                        "message" => "Successfully retrieved user.",
                        "data" => [
                            "user" => $user,
                            "roles" => $role,
                        ]
                    ]);
                });
                Route::post('logout', function (Request $request) {
                    $request->user()->currentAccessToken()->delete();
                    if( $request->user() ){
                        $logs = new Logs;
                        $logs->user_id = $request->user()->id;
                        $logs->type = "Logged Out";
                        $logs->save();
                    }
                    return response()->json([
                        "code" => 200,
                        "message" => "Successfully logged out a user."
                    ]);
                });
            });
        });
        Route::group([
            'middleware' => 'auth:sanctum',
            'prefix' => 'users',
        ], function () {
            Route::get("all", "UserController@all");
            Route::get("fetch/{user_id}", "UserController@fetch");
            Route::post('delete/{user_id}', 'UserController@delete');
            Route::post("define", "UserController@define");

            Route::post('assign/role/{user_id}', 'UserController@assignRole');
            Route::get('fetch/role/{user_id}', 'UserController@fetchRole');
            Route::post('remove/role/{user_id}', 'UserController@removeRole');

            Route::post('assign/permission', 'UserController@assignPermission');
            Route::post('manage/permission', 'UserController@managePermission');
            Route::get('fetch/permission/{user_id}', 'UserController@fetchPermission');
            Route::post('remove/permission/{user_id}', 'UserController@revokePermission');

            Route::get('fetch/personal/{user_id}', 'UserController@fetchPersonal');
            Route::post('change/password/{user_id}', 'UserController@resetPassword');
        });
    });

    Route::group([
        'middleware' => 'auth:sanctum'
    ], function(){

        Route::group([
            "namespace" => "Dashboard",
            "prefix" => "dashboard",
        ], function () {
            Route::get("/", "DashboardController@index");

        });

        Route::group([
            "namespace" => "Employee",
            "prefix" => "employees",
        ], function () {
            Route::get("all", "EmployeeController@all");
            Route::post("define", "EmployeeController@define");
            Route::get("fetch/{id}", "EmployeeController@fetch");
            Route::post('delete/{id}', 'EmployeeController@delete');

        });

        Route::group([
            "namespace" => "FlowControl",
            "prefix" => "flow_control",
        ], function(){

            Route::group([
                "namespace" => "Definition",
                "prefix" => "definitions",
            ], function(){

                Route::group([
                    "prefix" => "approvers",
                ], function(){
                    Route::get('all', 'ApproverController@all');
                    Route::get('delete/{id}', 'ApproverController@delete');
                    Route::get('fetch/{id}', 'ApproverController@fetch');
                    Route::get('fetch-email', 'ApproverController@fetchByEmail');
                    Route::post('define', 'ApproverController@define');
                });

                Route::group([
                    "prefix" => "rules"
                ], function(){
                    Route::get('all', 'RuleController@all');
                    Route::get('delete/{id}', 'RuleController@delete');
                    Route::get('fetch/{id}', 'RuleController@fetch');
                    Route::post('define', 'RuleController@define');
                });

            });

            Route::group([
                "namespace" => "Request",
                "prefix" => "requests",
            ], function(){
                Route::get('all', 'FlowControlRequestController@all');
                Route::get('delete/{id}', 'FlowControlRequestController@delete');
                Route::get('fetch/{id}', 'FlowControlRequestController@fetch');
                Route::post('define', 'FlowControlRequestController@define');
                Route::post('verify', 'FlowControlRequestController@verify');
                Route::post('approval', 'FlowControlRequestController@formApproval');

                Route::group([
                    "prefix" => "{request_id}",
                ], function(){

                    Route::group([
                        "prefix" => "approvers"
                    ], function(){
                        Route::get('all', 'FlowControlRequestApproverController@all');
                        Route::get('delete/{id}', 'FlowControlRequestApproverController@delete');
                        Route::get('fetch-status', 'FlowControlRequestApproverController@fetchRequestApproversStatus');
                        Route::get('fetch/{id}', 'FlowControlRequestApproverController@fetch');
                        Route::post('define', 'FlowControlRequestApproverController@define');

                        Route::get('approve/{id}', 'FlowControlRequestController@approve');
                        Route::get('reject/{id}', 'FlowControlRequestController@reject');

                    });

                    Route::group([
                        "prefix" => "rules"
                    ], function(){
                        Route::get("/", "FlowControlRequestRuleController@index");
                        Route::get('all', 'FlowControlRequestRuleController@all');
                        Route::get('delete/{id}', 'FlowControlRequestRuleController@delete');
                        Route::get('fetch/{id}', 'FlowControlRequestRuleController@fetch');
                        Route::post('define', 'FlowControlRequestRuleController@define');
                    });

                    Route::group([
                        "prefix" => "logs"
                    ], function(){
                        Route::get('all', 'FlowControlRequestLogController@all');
                        Route::get('delete/{id}', 'FlowControlRequestLogController@delete');
                        Route::get('fetch/{id}', 'FlowControlRequestLogController@fetch');
                        Route::post('define', 'FlowControlRequestLogController@define');
                    });

                });
            });

        });

        Route::group([
            "namespace" => "Definition",
            "prefix" => "definitions",
        ], function () {

            Route::group([
                "namespace" => "Equipment",
                "prefix" => "equipments",
            ], function () {

                Route::get("all", "EquipmentController@all");
                Route::post("define", "EquipmentController@define");
                Route::get("fetch/{id}", "EquipmentController@fetch");
                Route::post('delete/{id}', 'EquipmentController@delete');

            });

            Route::group([
                "namespace" => "Form",
                "prefix" => "forms",
            ], function () {

                Route::get("all", "FormController@all");
                Route::get("fetch/{id}", "FormController@fetch");
                Route::get("fetch-type", "FormController@fetchType");
                Route::post('delete/{id}', 'FormController@delete');
                Route::post("define", "FormController@define");
                Route::post("generate", "FormController@generate");
                Route::post("continuation-generate", "FormController@continueGenerate");
                Route::post("define-meta", "FormController@defineMeta");
                Route::post("define-flow-control", "FormController@defineFlowControl");
                Route::post("status/{id}", "FormController@status");
                Route::post("continuation-status/{id}", "FormController@continueStatus");
                Route::post("fetch-link-form", "FormController@linkForm");
                Route::post("fetch-job-order-form", "FormController@joForm");
                Route::post("report-email", "FormController@reportsEmail");
                Route::post("report-accomplishment", "FormController@reportsAccomplishment");

                Route::group([
                    "prefix" => "type",
                ], function () {
                    Route::get("all", "FormTypeController@all");
                    Route::post("define", "FormTypeController@define");
                    Route::get("fetch/{id}", "FormTypeController@fetch");
                    Route::post('delete/{id}', 'FormTypeController@delete');

                });

            });

            Route::group([
                "namespace" => "UserAssign",
                "prefix" => "user_assign",
            ], function () {

                Route::get("all", "UserAssignController@all");
                Route::post("define", "UserAssignController@define");
                Route::get("fetch/{id}", "UserAssignController@fetch");
                Route::post('delete/{id}', 'UserAssignController@delete');

            });

        });

        Route::group([
            "namespace" => "Utilities",
            "prefix" => "utilities",
        ], function () {

            Route::group([
                "namespace" => "File",
                "prefix" => "files",
            ], function () {

                Route::get("all", "FileController@all");
                Route::get("fetch/{id}", "FileController@fetch");
                Route::post("define", "FileController@define");
                Route::post('delete/{id}', 'FileController@delete');
                Route::post('upload', 'FileController@upload');

            });

            Route::group([
                "namespace" => "Meta",
                "prefix" => "metas",
            ], function () {

                Route::get("all", "MetaController@all");
                Route::post("define", "MetaController@define");
                Route::get("fetch/{id}", "MetaController@fetch");
                Route::get("fetch-by-target", "MetaController@fetchByTarget");
                Route::get("fetch-by-approvers", "MetaController@fetchByApprovers");
                Route::post('delete/{id}', 'MetaController@delete');

            });

        });

        Route::group([
            "namespace" => "Notification",
        ], function () {

            Route::group([
                "prefix" => "notifications",
            ], function () {
                Route::post("all", "NotificationController@all");
                Route::post("unread", "NotificationController@unread");
                Route::post("delete", "NotificationController@delete");
                Route::post("mark-as-read", "NotificationController@markAsRead");
                Route::post("send-notification", "NotificationController@sendNotification");
                Route::post("send-test", "NotificationController@sendTestEmail");

            });

        });

    });

});
