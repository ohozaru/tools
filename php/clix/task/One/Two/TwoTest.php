<?php
class TwoTest extends ClixTask
{
    const HINT = 'Two test task';

    public function execute() 
    {
        echo exec("echo Wait 1sec and execute command");
        echo "\n";

        sleep(1);
    }
}
