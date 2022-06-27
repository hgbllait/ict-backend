<?php

namespace App\Http\Controllers\FlowControl;

class ChainController {

    protected $first = null;


    public function generateLink( $link_map, $return_as_function=true ){

        $first = null;
        $predecessor = null;
        $successor   = null;

        if( is_array( $link_map ) && !empty( $link_map )){

            foreach( $link_map as $key => $value ){

                if( !is_array( $value )){
                    $object = ( new $value );

                    if( $successor == null && $predecessor == null ){
                        $first       = $object;
                        $predecessor = $object;
                    } else if( $value ) {
                        $predecessor->succeedWith ( $object );
                        $predecessor = $object;
                    }

                } else {

                    foreach( $value as $key_ => $value_ ){
                        $object = ( new $value_ );

                        if( $successor == null && $predecessor == null ){
                            $first       = $object;
                            $predecessor = $object;

                        } else if( $value_ ) {
                            $predecessor->succeedWith ( $object );
                            $predecessor = $object;
                        }
                    }
                }
            }

            if( !$return_as_function ){
                $this->first = $first;

                return $this;
            }

        }

        return $first;

    }

    public function execute( $data, $params ){
        $function = $data;

        if( !is_null( $this->first ) ){
            $function = $this->first;
        }

        return $function->handle( $data, $params );
    }
}
