<?php

namespace App\Links\Rules\FlowControl\Rules;

class RequiredRule extends \App\Links\Rules\FlowControl\BaseFlowControlRule
{
    public function handle( $data, $params )
    {

        if ( isset( $params[ 'approvers' ] ) ) {
            if ( isset( $params[ 'approvers' ] ) ) {
                foreach( (array)$params[ 'approvers' ] as $key => $approver ) {
                    if ( $approver[ 'approval_status' ] == "rejected" && $approver[ 'required' ] == "true" ) {
                        $data['status'] = "rejected";
                        $data['approval_status'] = false;
                        $data['update_request'] = false;

                        return $data;
                    }
                }
            }
        }

        return $this->next( $data, $params );
    }
}
