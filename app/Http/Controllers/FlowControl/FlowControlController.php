<?php


namespace App\Http\Controllers\FlowControl;

use App\Helpers\FlowControlHelper;
use App\Http\Controllers\BaseController;

class FlowControlController extends BaseController
{
    protected
        $chain,
        $flow_control_helper;


    public $override, $consensus, $required;

    public function __construct(

        ChainController $chain,
        FlowControlHelper $flowControlHelper

    )
    {
        $this->flow_control_helper = $flowControlHelper;
        $this->chain = $chain;
    }

    public function generate( $data,  $params )
    {
        return $this->chain->generateLink( $this->getLinkMap(), false )
             ->execute( $data, $params );
    }

    protected function getLinkMap()
    {
        return $this->flow_control_helper->link_map;
    }

    protected function buildChain()
    {
        $this->chain->generateLink( $this->flow_control_helper->link_map );
    }
}
