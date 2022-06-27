<?php

namespace App\Http\Controllers\Notification;

use App\Data\Models\Request\FlowControlRequest;
use App\Data\Models\Request\FlowControlRequestApprover;
use App\Http\Controllers\BaseController;
use App\Data\Models\Auth\User;
use App\Http\Controllers\Utilities\Meta\MetaController;
use App\Mail\TestEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificationController extends BaseController
{
    protected $meta_controller;

    function __construct(
        MetaController $metaController
    ){
        $this->meta_controller = $metaController;
        // middleware
    }

    public function all(){
        $userSchema = User::first();
        $notifications = $userSchema->notifications;
        $result = [];
        foreach ($notifications as $notification) {
            $result[] = $notification->toArray();
        }

        dd($result);
    }

    public function unread(){
        $userSchema = User::first();
        $notifications = $userSchema->unreadNotifications;
        $result = [];
        foreach ($notifications as $notification) {
            $result[] = $notification;
        }

        dd($result);
    }

    public function delete(){
        $userSchema = User::find(1);
        $userSchema->notifications()->delete();

        dd('Deleted completed!');
    }

    public function markAsRead(){
        $userSchema = User::find(1);

        //
        $userSchema->unreadNotifications
            ->markAsRead();


        dd('Task completed!');
    }

    public function sendNotification(Request $request)
    {
        $data = $request->all();

        //region data validation
        foreach( ['approver_id', 'request_id'] as $value ){
            if(!isset($data[$value])){
                return response()->json([
                    'code' => 404,
                    'message' => $value. " is not set."
                ], 404);
            }
        }
        //endregion data validation

        $link = $this->meta_controller->fetchByTarget(new Request([
            'meta_key' => 'link',
            'target_id' => $data['approver_id'],
            'target_type' => 'request_approver',
            'single' => true
        ]));

        if( isset($link)
            && !is_code_success( $link->status() ) ){
            return response()->json([
                "code"       => 500,
                "message"      => "Link not found.",
            ], 500);
        }

        $link = $link->getData()->data->metas->meta_value;

        $password = $this->meta_controller->fetchByTarget(new Request([
            'meta_key' => 'password',
            'target_id' => $data['approver_id'],
            'target_type' => 'request_approver',
            'single' => true
        ]));

        if( isset($password)
            && !is_code_success( $password->status() ) ){
            return response()->json([
                "code"       => 500,
                "message"      => "Password not found.",
            ], 500);
        }

        $password = $password->getData()->data->metas->meta_value;

        $approver = FlowControlRequestApprover::where('id', $data['approver_id'])->with('approver')->first();
        if( !$approver ){
            return response()->json([
                "code"       => 500,
                "message"      => "Approver ID does not exist.",
            ], 500);
        }

        $flow_control_request = FlowControlRequest::where('id', $data['request_id'])->with('forms')->first();
        if( !$flow_control_request ){
            return response()->json([
                "code"       => 500,
                "message"      => "Request ID does not exist.",
            ], 500);
        }

        $message = 'Just to remind you about the form: '. $flow_control_request['forms']['name']. '.';
        $notification = $this->sendLinkPassword($message,
            $flow_control_request['forms']['name'],
            $approver['approver']['name'],
            $link,
            $password,
            $approver['approver']['email']);

        if(isset($notification)
            && !is_code_success( $notification->status() ) ){
            return response()->json([
                "code" => 500,
                "message" => "Failed to send a notification."
            ], 500);
        }

        return response()->json($notification->getData());

    }

    public function sendLinkPassword($message, $form_name, $approver_name, $link, $password, $email_to){

        $data = [
            'message' => $message,
            'form_name' => $form_name,
            'approver_name' => $approver_name,
            'link' => env('FRONTEND_URL', 'localhost:4200').'/form/'.$link,
            'password' => $password
        ];

        Mail::to($email_to)->send(new TestEmail($data));

        if (Mail::failures()) {
            return response()->json([
                "code"       => 500,
                "message"      => "Failed to send a notification",
            ], 500);
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully send a notification",
        ]);

    }

}
