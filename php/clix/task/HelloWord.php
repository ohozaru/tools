<?php
class HelloWord extends ClixTask
{
    const HINT = 'Example HelloWord task';
    const DESCRIPTION = <<<TXT
HelloWord is just an example, self explained.
TXT;

    public $params_config = '{ 
        "firstname": {
            "description": "Firstname used for output message",
            "default": "Adam"
        },
        "lastname": {
            "description": "Lastname used for output message"
        },
        "--uppercase": {
            "mandatory": false,
            "description": "Print name uppercase",
            "default": false
        }
    }';

    public function execute() 
    {
        echo exec("echo Wait 1sec and execute command");
        echo "\n";

        sleep(1);

        if($this->uppercase) {
            $this->firstname = strtoupper($this->firstname);
            $this->lastname = strtoupper($this->lastname);
        }

        $cmd = sprintf("echo Hello %s %s",
            $this->firstname,
            $this->lastname
        );

        if(Clix::ask('Are you ready [yes|no]') != 'yes')
            die(':(');

        echo exec($cmd);
    }
}
