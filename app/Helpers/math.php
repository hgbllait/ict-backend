<?php

use Carbon\Carbon;

if( !function_exists( "app_hash" ) ){
    /**
     * Creates a "app" hash to prevent ID tampering attack
     *
     * @param $value
     * @param string $func
     * @param boolean $timestamp
     * @return string
     */
    function app_hash( $value, $func="sha1", $timestamp=false ){
        $time = Carbon::now()->format("Ym");
        $app = $value . ( $timestamp == true ? $time : "" );

        return quick_hash( $app, 0, 0, $func );
    }
}

if (!function_exists("between")) {
    /**
     * Check if value is in between.
     *
     * @param float $value
     * @param float $min
     * @param float $max
     * @return boolean
     */
    function between($value, $min, $max)
    {
        return ($min <= $value) && ($value <= $max);
    }
}

if (!function_exists("nf")) {
    /**
     * Creates a number format from a given value.
     *
     * @param integer $value
     * @param integer $places
     * @return number
     */
    function nf($value = 0, $places = 2)
    {
        return number_format($value, $places);
    }
}

if ( !function_exists( 'is_numeric_pk' ) ) {
    function is_numeric_pk( $number )
    {
        return ( is_numeric( $number ) && $number >= 0 );
    }
}

if ( !function_exists( "percentage" ) ) {
    function percentage( $count, $base )
    {
        return ( $count / $base ) * 100;
    }
}

if (!function_exists("quick_hash")) {
    /**
     * Creates a quick hash from a value
     *
     * @param string $value
     * @param integer $from
     * @param integer $to
     * @param string $func
     * @return string
     */
    function quick_hash($value, $from = 0, $to = 25, $func='sha256')
    {
        if( !in_array( $func, hash_algos () ) ){
            $func = "basic_hash";
        }

        $force = [ "sha256" ];
        if( in_array( $func, $force ) && !function_exists($func) ){
            $hash = hash( $func, $value );
        } else {
            $hash = $func( $value );
        }

        return ( $to > 0 ? substr($hash, $from, $to) : $hash );
    }
}

if( !function_exists("basic_hash") ){
    function basic_hash( $value ){
        return md5( $value );
    }
}

if( !function_exists("hashf") ){
    function hashf( $value, $algo="sha256"){
        return hash( $algo, $value );
    }
}

if (!function_exists("round_value")) {
    /**
     * Round up value with precision.
     *
     * @param mixed $value refers to the value beign rounded up
     * @param integer $precision refers to the precision of decimal places to be rounded up
     * @param boolean $mode true if round up else round down
     * @return mixed
     */
    function round_value($value, $precision = 5, $mode = true)
    {
        $computed = ($value * pow(10, $precision));
        return ($mode == true ? ceil($computed) : floor($computed)) / pow(10, $precision);
    }

}

if( !function_exists( "sessioned_hash" ) ){
    /**
     * Creates a "sessioned" hash to prevent ID tampering attack
     *
     * @param $value
     * @param string $func
     * @return string
     */
    function sessioned_hash( $value, $func="sha1" ){
        $salt = env("SESSIONED_HASH_SALT", config( "helper.session_salt" ) );
        $timestamp = Carbon::now()->format("YmW");
        $sessioned = $value . $salt . $timestamp;

        return quick_hash( $sessioned, 0, 0, $func );
    }
}

if( !function_exists( "validate_sessioned_hash") ){
    /**
     * Validates a provided value with a given hash
     *
     * @param $value
     * @param $hash
     * @param string $func
     * @return bool
     */
    function validate_sessioned_hash( $value, $hash, $func="sha1" ){
        return sessioned_hash( $value, $func ) === $hash;
    }
}

if( !function_exists( "validate_app_hash") ){
    /**
     * Validates a provided value with a given hash
     *
     * @param $value
     * @param $hash
     * @param string $func
     * @param boolean $timestamp
     * @return bool
     */
    function validate_app_hash( $value, $hash, $func="sha1", $timestamp=false ){
        return app_hash( $value, $func, $timestamp ) === $hash;
    }
}

if( !function_exists("generate_random_string") ){
    function generate_random_string( $length = 8 )
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
