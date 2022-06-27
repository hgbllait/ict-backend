<?php

use Carbon\Carbon;
use Carbon\CarbonPeriod;

if( !function_exists ( 'carbon_now') ){
    /**
     * @param string $tz
     * @return Carbon
     */
    function carbon_now( $tz="Asia/Manila" ){
        return Carbon::now( env("APP_TIMEZONE", $tz ) );
    }
}

if( !function_exists ( 'carbon_parse') ){
    /**
     * @param $data
     * @param string $tz
     * @return Carbon
     */
    function carbon_parse( $data, $tz="Asia/Manila" ){
        return Carbon::parse( $data, env("APP_TIMEZONE", $tz ) );
    }
}

if( !function_exists ('readable_date_range') ){
    function readable_date_range( $start, $end ){
        $startTime = strtotime( $start );
        $endTime = strtotime( $end );

        if( date('Y', $startTime ) == date ( 'Y',$endTime ) ){
            if( ( date( 'j',$startTime ) == 1 )
                && ( date( 'j',$endTime ) == date( 't',$endTime ) )
            ){
                if( date('F',$startTime) == date('F',$endTime) ) {
                    return date('F, Y',$endTime);
                }
                return date('F',$startTime) . "-" . date('F, Y',$endTime);
            }else{
                if( date('m',$startTime) == date ('m',$endTime) ){
                    return date('F j',$startTime) . "-" . date('j, Y',$endTime);
                }
                return date('F j',$startTime) . "-" . date('F j, Y',$endTime);
            }
        }

        return date('F j, Y',$startTime) . "-" . date('F j, Y',$endTime);

    }
}


if( !function_exists ( 'carbon_days_of_month') ){
    function carbon_days_of_month( $month, $year=0, $max_days=0 ){
        $year = $year === 0 ? date("Y") : $year;
        $month = validate_month ( $month );
        $from_date = $year . "-" . $month . "-01";
        $to_date =  $year . "-" . $month . "-" . date('t', strtotime($from_date) );
        $max_days = $max_days > 0 ? $max_days : date('t', strtotime($from_date) );

        $period = CarbonPeriod::create($from_date, $to_date);
        $days = [];
        $day_count = 0;

        // Iterate over the period
        foreach ($period as $date) {
            if( $day_count < $max_days && $max_days != 0 ) {
                $days[] = $date->format ( 'Y-m-d' );
                $day_count++;
            }
        }

        return $days;
    }
}

if( !function_exists( 'object_has_trait') ){
    function object_has_trait( $trait, $object ){
        return in_array( $trait, class_uses_recursive( $object ));
    }
}

if( !function_exists("resolve_data") ){
    function resolve_data( $data=null, $target="", $default="" ){

        if( isset( $data[ $target ] ) &&
            !is_null( $data[ $target ] )
        ){
            return $data[ $target ];
        }

        return $default;
    }
}

if( !function_exists( 'stringify_boolean') ){
    function stringify_boolean( $value ){
        return $value === true ? "true" : "false";
    }
}

if( !function_exists ( "swap_values") ){
    function swap_values( &$from, &$to ){
        $temp = $from;
        $from = $to;
        $to = $temp;
    }
}

if( !function_exists("validate_month") ){
    function validate_month( $month=0 ){
        $month = $month === 0 ? carbon_now()->format ("m") : $month;

        if( $month > 12 || $month < 1 || !is_numeric( $month )){
            $month = date("m");
        }

        return $month;
    }
}

if( !function_exists("validate_pk") ){
    function validate_pk( $data=null, $target="id", $default="" ){

        if( isset( $data[ $target ] ) &&
            !is_null( $data[ $target ] )
        ){
            return $data[ $target ];
        }

        return $default;
    }
}
