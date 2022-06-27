<?php

if (!function_exists("check_constant")) {
    /**
     * Checks for a value in a given set of constants.
     *
     * @param mixed $value Value to be checked
     * @param array $constants
     * @return mixed
     */
    function check_constant($value, $constants = [])
    {
        if (str_contains($value, "@@")) {
            switch (str_replace("@@", "", $value)) {
                case "fod_fullname":
                case "tsd_fullname":
                case "executive_officer_fullname":
                case "managing_partner_fullname":
                case "user_fullname":
                case "user_type":
                    $value = $constants[str_replace("@@", "", $value)];
                    break;
            }
        }

        return $value;
    }
}

if (!function_exists("is_code_success")) {
    /**
     * Checks for a given number and returns true if it is between 200 and 299
     *
     * @param integer $code
     * @return bool
     */
    function is_code_success($code)
    {
        return between($code, 200, 299);
    }
}

if (!function_exists("multi_stripos")) {
    /**
     * strpos using an array as needle
     *
     * @param [type] $string
     * @param [type] $check
     * @param boolean $getResults
     * @return void
     */
    function multi_stripos($string, $check, $getResults = false)
    {
        $result = [];
        $check = (array) $check;

        foreach ($check as $s) {
            $pos = stripos($string, $s);

            if ($pos !== false) {
                if ($getResults) {
                    $result[$s] = $pos;
                } else {
                    return $pos;
                }
            }
        }

        return empty($result) ? false : $result;
    }
}
