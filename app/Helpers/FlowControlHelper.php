<?php

namespace App\Helpers;

class FlowControlHelper
{

  public $link_map = [
      'consensus'  => \App\Links\Rules\FlowControl\Rules\ConsensusRule::class,
      'required'  => \App\Links\Rules\FlowControl\Rules\RequiredRule::class,
  ];

}
