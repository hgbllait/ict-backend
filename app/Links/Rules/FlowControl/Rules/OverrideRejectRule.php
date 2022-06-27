<?php

namespace App\Links\Rules\FlowControl\Rules;

class OverrideRejectRule extends \App\Links\Rules\FlowControl\BaseFlowControlRule
{
    public function handle( $data, $params ){

        if( isset( $params['approvers'] ) ){
            foreach( $params['approvers'] as $key => $approver ){

                if( isset($approver['override_reject']) &&
                    $approver['override_reject'] === "true" &&
                    $approver['approval_status'] === "rejected" ){
                    $data['status'] = "rejected";
                    $data['approval_status'] = true;
                    $data['update_request'] = true;

                    return $data;
                }
            }
        }

        return $this->next( $data, $params );
    }
}
