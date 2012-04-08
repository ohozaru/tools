<?php

function mpr($val, $die=false, $steps = 0) {
    if(!headers_sent()) 
        header("content-type: text/plain");

    if (is_array($val) || is_object($val)) {
        print_r($val);

        if(is_array($val))
            reset($val);
    }   
    else {
        var_dump($val);
    }

    if($die) {   
        $trace = debug_backtrace();
        echo "--\n";
        echo sprintf('Who called me: %s line %s', $trace[0]['file'], $trace[0]['line']);
        if($steps) {
            echo "\nTrace:";
            for($i = 1; $i <= $steps; $i++) {
                echo sprintf("\n%s line %s", $trace[$i]['file'], $trace[$i]['line']);
            }
        }
        die();
    }   
}

function math_round($value, $precision = 0) {
    return round(round($value*pow(10, $precision+1), 0), -1) / pow(10, $precision+1);
}

function report_memory_usage($format = 'MB', $round = 3) {
    switch($format) {
        case 'MB':
            return math_round(memory_get_usage() / 1048576, 3);
        break;
        case 'KB':
            return math_round(memory_get_usage() / 1024, 3);
        break;
        default:
            throw new InvalidArgumentException('Unrecognized format');
        break;
    }
}

function report_memory_usage_as_string($format = 'MB') {
    return sprintf("Memory used: %.3f MB\n", report_memory_usage($format));
}
