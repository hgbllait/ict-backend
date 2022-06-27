<?php

namespace App\Links\Rules\FlowControl\Rules;

class OverrideAcceptRule extends \App\Links\Rules\FlowControl\BaseFlowControlRule
{
    public function handle( $data, $params ){
        if( isset( $params['approvers'] ) ){
            foreach( $params['approvers'] as $key => $approver ){

                if( isset($approver['override_accept']) &&
                    $approver['override_accept'] === "true" &&
                    $approver['approval_status'] === "approved" ){
                    $data['status'] = "approved";
                    $data['approval_status'] = true;
                    $data['update_request'] = false;

                    return $data;
                }
            }
        }

        return $this->next( $data, $params );
    }
}
