<?php

interface ClixTaskInterface {}
class ClixTaskException extends Exception {}
abstract class ClixTask implements ClixTaskInterface {

    const HINT = '<<<missing HINT>>>';
    const DESCRIPTION = '<<<missing DESCRIPTION>>>';
    const BACKSPACE = "\x08";

    public $startTime;
    public $executionTime;

    protected $params_config = '{
        "--help": {
            "mandatory": false,
            "description": "Show help for command"
        }
    }'; //json configuration of task parameters

    protected function getParamsConfiguration() {
        static $config;

        if($config)
            return $config;

        if(! ($config = json_decode($this->params_config, true))) {
            echo 'Invalid params_config: json_last_error value :'.json_last_error();
            exit;
        }

        return $config;
    }

    final function __construct(array $parameters = null) {

        $this->_startTime = microtime(true);

        if($parameters && array_key_exists('--help', $parameters)) {
            $this->help(); 
            exit;
        }

        if($parameters || $parameters === array()) {
            $this->setupParameters($parameters);
        }

        $this->init();
    }

    public function init() {}

    public function __destruct() {
        $this->_execution_time = $this->getExecutionTime();
    }
    
    public function getExecutionTime() {
        $time  =  ceil(1000 * (microtime(true) - $this->_startTime));
        $time .= 'ms';
        return $time;
    }

    public function setupParameters(array $parameters) {

        $config = $this->getParamsConfiguration();

        foreach($config as $key => $option) {

            $option = (object) $option;

            if($this->isParamMandatory($key))
                if(!array_key_exists($key, $parameters))
                    Clix::error('[%s] parameter is mandatory', $key);

            if(array_key_exists($key, $parameters))
            {
                $name = $parameters[$key]->name;
                $this->$name = $parameters[$key]->value;
            }
            else
            {
                $name = ltrim($key, '-');
                $this->$name = $this->getDefaultValue($key);
            }
        }

        //final check if all passed parameters are correct
        foreach($parameters as $key => $value)
            if(!array_key_exists($key, $config))
                Clix::error('Unknown parameter [%s] use --help for more information', $key);
    }

    public function getArgumentList() {
        return array_keys($this->getParamsConfiguration());
    }

    public function getDefaultValue($param) {
        $config = $this->getParamsConfiguration();

        if(array_key_exists('default', $config[$param]))
            return $config[$param]['default'];

        return null;
    }

    public function getParamDescription($param) {
        $config = $this->getParamsConfiguration();

        if(array_key_exists('description', $config[$param]))
            return $config[$param]['description'];

        return '<<< no description >>>';
    }

    public function isParamMandatory($param) {
        $config = $this->getParamsConfiguration();

        if(array_key_exists('mandatory', $config[$param]))
            return $config[$param]['mandatory'];

        return true;
    }

    public function help()
    {
        $taskName = get_class($this);
        $hint = constant($taskName . '::HINT');
        $scriptName = Clix::$scriptName;
        $description = constant($taskName . '::DESCRIPTION');

        foreach(array_keys($this->getParamsConfiguration()) as $param) {
            $mandatory = $this->isParamMandatory($param) ? '!' : ' ';

            $valid_options[] = 
                '  '.$param."\t: $mandatory ".$this->getParamDescription($param)
                .' ('
                .'default: '.( is_bool($default = $this->getDefaultValue($param)) ? (($default) ? 'True' : 'False') : (($default) ? $default : 'NULL'))
                .')';
        }

        $valid_options = join("\n", $valid_options);

        $border_length = 10;
        //get the longes line from hint to calculate border length
        foreach(explode("\n", $hint) as $line) {
            $border_length = (strlen($line) > $border_length) ? strlen($line) : $border_length;
        }

        $border = str_repeat('*', $border_length);

        $message = <<< EOT
$border
$hint
$border
Usage: 

  php $scriptName $taskName [options]

Valid options:
$valid_options

MORE:
$description
EOT;
        Clix::message($message);
    }
}
