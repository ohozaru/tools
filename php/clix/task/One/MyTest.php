<?php
class MyTest extends ClixTask
{
    const HINT = 'My test task';

    public function execute() 
    {
        echo exec("echo Wait 1sec and execute command");
        echo "\n";

        sleep(1);
    }
}
