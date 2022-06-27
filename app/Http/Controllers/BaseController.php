<?php

namespace App\Http\Controllers;

class BaseController extends Controller
{
    protected function makeModel( $data, $model ){
        if( isset( $data['fresh']) && $data['fresh'] == true ){
            $model = refresh_model ($model);
        }

        return $model;
    }
}
