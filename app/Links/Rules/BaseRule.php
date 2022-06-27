<?php

namespace App\Links\Rules;


class BaseRule extends \Shared\BaseClasses\ResponsiveLink
{
    public function handle( $data, $params ){
        return parent::handle( $data, $params );
    }
}
