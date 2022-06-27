<?php

namespace App\Http\Controllers\Dashboard;

use App\Data\Models\Auth\Logs;
use App\Data\Models\Auth\User;
use App\Data\Models\Definition\Form\Form;
use App\Data\Models\Employee\Employee;
use App\Data\Models\Request\FlowControlRequest;
use App\Data\Models\Request\FlowControlRequestApprover;
use App\Data\Models\Request\FlowControlRequestLog;
use App\Data\Models\Utilities\Meta\Meta;
use App\Http\Controllers\FlowControl\Request\FlowControlRequestApproverController;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

class DashboardController extends BaseController
{

    protected $flow_request_approver_ctr;
    function __construct(
        FlowControlRequestApproverController $flowControlRequestApproverController
    ){
        $this->flow_request_approver_ctr = $flowControlRequestApproverController;
    }

    protected $fillable = [
        'target_type',
        'target_id',
        'meta_key',
        'meta_value',
    ];

    public function index( Request $request )
    {
        if ($request->user() === null) {
            return response()->json([
                "code" => 401,
                "message" => "You are not logged in"
            ], 401);
        }

        $id = $request->user()->id;
        $approver_id = 0;

        $user_assign = User::with('approver')
            ->where('id', $id)->first()->toArray();
        if($user_assign['approver'] != null){
            $approver_id = $user_assign['approver']['approver_id'];
        }
        $login_logs = Logs::with('user')->orderBy('id', 'desc')->take(7)->get();
        foreach( $login_logs as $value ){
            $value['user']['employee'] = Employee::find($value['user']['employee_id']);
        }

        $all_form = Form::with('type')->orderBy('id', 'desc')->take(5)->get();

        $request_log = FlowControlRequestLog::orderBy('id', 'desc')->take(13)->get();

        $pending_request = FlowControlRequestApprover::where('approver_id', $approver_id)
            ->where('approval_status', '=', 'false')->orderBy('id', 'desc')->take(5)->get();

        $form_request = FlowControlRequestApprover::where('approver_id', $approver_id)
            ->orderBy('id', 'desc')->take(5)->get();

        $pending_total = FlowControlRequest::where('approval_status', '=', 'false')->count();

        $completed_total = FlowControlRequest::where('approval_status', '!=', 'false')->count();

        $my_request_count = FlowControlRequestApprover::where('approver_id', $approver_id)->count();

        foreach($pending_request as $value){
            $value['request'] = FlowControlRequest::where('id', $value['flow_control_request_id'])->first();
            $value['request']['forms'] = Form::with('type')->where('id', $value['request']['form_id'])->first();
        }

        foreach($form_request as $value){
            $value['request'] = FlowControlRequest::where('id', $value['flow_control_request_id'])->first();
            $value['request']['status'] = $this->flow_request_approver_ctr->fetchRequestApproversStatus(new Request(), $value['request']['id'])->getData()->data;
            $value['request']['forms'] = Form::with('type')->where('id', $value['request']['form_id'])->first();

            $has_job_order = Meta::where('meta_key', 'job_order_no')
                ->where('target_type', '=', 'forms')
                ->where('target_id', '=',  $value['request']['form_id'])->pluck('meta_value')->first();
            if(!$has_job_order
                && $value['request']['forms']['type_id'] == 1
                && $value['request']['approval_status'] === 'approved') $value['request']['approval_status'] = 'processing';
            if($value['added_by'] == 0){
                $value['added_by'] = [
                   'full_name' => 'System generated'
                ];
            } else {
                $user = User::with('employee')->where('id', $value['added_by'])->first();
                $value['added_by'] = $user['employee'];
            }
        }

        return response()->json([
            "code"       => 200,
            "message"      => "Successfully retrieved dashboard.",
            "data"       => [
                'stats' => [
                    'pending' => $pending_total,
                    'completed' => $completed_total,
                    'my_request' => $my_request_count
                ],
                'login_logs' => $login_logs,
                'activities' => $request_log,
                'form_request' => $form_request,
                'pending_request' => $pending_request,
                'all_form_request' => $all_form
            ]
        ]);

    }

}
