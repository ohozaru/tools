<?php
require_once ('Service.php');
Service::init(array(
    'service_path' => __DIR__ . '/../services/',
    'service_namespace' => 'Service',
));

//debug method
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
