<?php

namespace App\Links\Rules\FlowControl\Rules;

/**
 * Class ConsensusRule
 * @package App\Links\Rules
 */
class ConsensusRule extends \App\Links\Rules\FlowControl\BaseFlowControlRule
{
    public function handle( $data, $params ){

        if( isset($params['consensus']['percentage']) && $params['consensus']['percentage'] > 0 ){
            $accept = 0;
            $reject = 0;
            foreach( $params['approvers'] as $key => $approver ){
                if( $approver['approval_status'] == "rejected" && $approver['required'] == "true"){
                     continue;
                 }
                if( $approver['approval_status'] == "approved" ){
                    $accept++;
                }
                if( $approver['approval_status'] == "rejected" ){
                    $reject++;
                }
            }
            if( percentage( $accept, count( $params['approvers'] ) ) >= $params['consensus']['percentage'] ){
              $data['status'] = "approved";
              $data['approval_status'] = true;
              $data['update_request'] = true;

              return $data;
            }
            if( percentage( $reject, count( $params['approvers'] ) ) >= $params['consensus']['percentage'] ){
              $data['status'] = "rejected";
              $data['approval_status'] = false;
              $data['update_request'] = true;

              return $data;
            }

        }
        else{
          $accept = 0;
          $reject = 0;
          foreach( $params['approvers'] as $key => $approver ){
              if( $approver['approval_status'] == "approved"
              || $approver['override_accept'] == "approved"){

                  $accept++;
              }
              if( $approver['approval_status'] == "rejected"
                  || $approver['override_accept'] == "rejected" ){
                  $reject++;
              }
          }
          if( $reject > 0){
            $data['status'] = "rejected";
            $data['approval_status'] = true;
            $data['update_request'] = true;
            return $data;
          } else {
              if( count( $params['approvers'] ) == $accept ){
                  $data['status'] = "approved";
                  $data['approval_status'] = true;
                  $data['update_request'] = true;
                  return $data;
              }
          }
        }

        return $this->next( $data, $params );
    }
}
