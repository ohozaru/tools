<?php
/**
 * Clix
 *
 * @author Roman Nowicki <peengle@gmail.com>
 */
require_once __DIR__ . '/ClixTask.php';
require_once __DIR__ . '/ClixParameter.php';

class Clix {
    static public $scriptName;
    public $taskDirectory;

    public function __construct($taskDirectory = null) {
        global $argv;

        self::$scriptName = array_shift($argv);

        //be sure to add directory separator to the end of directory path
        if(substr($taskDirectory, -1) != DIRECTORY_SEPARATOR)
            $taskDirectory .= DIRECTORY_SEPARATOR;

        $this->taskDirectory = ($taskDirectory) 
            ? $taskDirectory 
            : __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function run() {
        global $argv;

        if(count($argv))
            $taskName = array_shift($argv);
        else
            $taskName = '--list';

        if($taskName == '--list') {
            return $this->noActionSpecified();
        }

        $className = $this->includeTask($taskName);

        $this->runTask($className, $argv);
    }

    public function runTask($className, array $parameters) {

        $args = array();
        foreach($parameters as $parameter)
        {
            $Param = new ClixParameter($parameter);
            $args[$Param->keyword] = $Param;
        }

        if(!class_exists($className))
            $this->includeTask($className);

        $Task = new $className($args);
        try {
            $Task->execute();
        } catch (InvalidArgumentException $e) {
            echo $e->getMessage()."\n";
            exit;
        } catch (ClixTaskException $e) {
            echo $e->getMessage()."\n";
            exit;
        }
    }

    /**
     * Echo list of task available
     * 
     * @return void
     */
    public function noActionSpecified() {
        echo "\nNo task specified. Select one from below :\n--------------------------------------\n";

        $list = array();
        $maxTaskNameLength = 0;
        $taskList = $this->retriveTaskList($this->taskDirectory);
        foreach($taskList as $path => $taskPathList) {
            $path = ($path) ? $path . '/' : $path;
            foreach($taskPathList as $taskName) {
                $list[$path][$taskName] = $this->getTaskHint($taskName);
                $nameLength = (strlen($taskName) + strlen($path));
                $maxTaskNameLength = ($nameLength > $maxTaskNameLength) ? $nameLength : $maxTaskNameLength;
            }
        }

        foreach($list as $path => $taskList) {
            foreach($taskList as $name => $hint) {
                echo sprintf("%s : %s\n", str_pad($path.$name, $maxTaskNameLength), $hint);
            }
        }

        echo "\n\n";
    }

    /**
     * Returns hint specified inside task in HINT contant
     * 
     * @param string $taskName 
     * @return string
     */
    public function getTaskHint($taskName) {
        return constant($taskName.'::HINT');
    }

    public function showTaskHelp($taskName) {
        $this->includeTask($taskName);
        $Task = new $taskName;
        $Task->help();
    }

    /**
     * includes php file correlated with task name and return its className
     * 
     * @param mixed $taskName 
     * @access public
     * @return string
     */
    public function includeTask($taskName) {
        if(is_file($taskName)) {
            $file = $taskName;
        } else {
            $file =  $this->taskDirectory . $taskName.'.php';

            if(!is_file($file)) {
                $this->message('Invalid task name: use --list to show all available tasks');
                exit -1;
            }
        }

        if(!is_readable($file)) {
            $this->message('File %s is not readable', $file);
            exit -2;
        }

        require_once $file;

        if(!$this->isInstanceOfTaskClass(pathinfo($file, PATHINFO_FILENAME))) {
            $this->message('%s is invalid type', $taskName);
            exit -3;
        }

        return pathinfo($file, PATHINFO_FILENAME);
    }

    /**
     * Verify if className is valid Task Class
     * 
     * @param string $className 
     * @return bool
     */
    protected function isInstanceOfTaskClass($className) {
        $Class = new ReflectionClass($className);

        if($Class->isAbstract())
            return false;

        if(!$Class->implementsInterface('ClixTaskInterface'))
            return false;

        return true;
    }

    /**
     * Returns array with names of all task from inside directory
     * 
     * @param path $directory 
     * @return array
     */
    protected function retriveTaskList($directory) {
        static $task = array();
        $TaskDirecotry = new DirectoryIterator($directory);

        foreach($TaskDirecotry as $File)
        {
            if($File->isDot())
                continue;
            if($File->isDir()) {
                $this->retriveTaskList($File->getPathname());
                continue;
            }

            $fileinfo = (object) pathinfo($File->getPathname());

            if(!isset($fileinfo->extension))
                continue;

            if($fileinfo->extension != 'php')
                continue;

            require_once($File->getPathname());

            if(!$this->isInstanceOfTaskClass($fileinfo->filename))
                continue;

            $task[ltrim(str_replace(rtrim($this->taskDirectory, '/'), '', dirname($File->getPathname())),'/')][] = $fileinfo->filename;
        }

        return $task;
    }

    public function retriveTaskArguments($taskName) {
        $this->includeTask($taskName);
        $Task = new $taskName;
        return $Task->getArgumentList();
    }

    static public function error($message) {
        $args = func_get_args();

        if(count($args) == 1)
            echo $message."\n";
        else
            echo call_user_func_array('sprintf', $args)."\n";

        exit;
    }

    /**
     * Helper for sprintf
     * Usage: $this->message('my name is %s %s', 'tom kowalski');
     * 
     * @param string $message 
     * @return void
     */
    static public function message($message) {
        $args = func_get_args();

        if(count($args) == 1)
            echo $message;
        else
            echo call_user_func_array('sprintf', $args);

        echo "\n";
    }

    /**
     * Ask user for input
     * 
     * @param string $message 
     * @param mixed $default_value 
     * @return user_input
     */
    static public function ask($message, $default_value = null) {

        ob_start();
        self::message($message." ($default_value): ", $default_value);
        $message = trim(ob_get_contents());
        ob_end_clean();
        echo $message;

        $handle = fopen ('php://stdin','r');
        $line = fgets($handle);
        
        return (trim($line) === '') ? $default_value : trim($line);
    }

    /**
     * Helper for generating list of choice for user in cli mode
     * Returns choosen option by user
     * 
     * @param array $options
     * @param integer $default 
     * @return integer
     */
    static public function options(array $options, $default = null) {
        foreach($options as $key => $value)
            self::message("[%s]\t-> %s", $key, $value);

        $selection = self::ask('Choose one from above', $default);

        if(!array_key_exists($selection, $options))
            self::options($options);

        return $selection;
    }

    /**
     * Helper for exit method, it will stop executing task and could display info message
     * @return void
     */
    static public function stop($message="Stoped\n") {
        forward_static_call_array(array(__CLASS__, 'message'), func_get_args());
        exit;
    }

    /**
     * Helper for passthru command by this method we can execute different program
     * @return void
     */
    static public function exec($cmd) {
        passthru($cmd);
    }
}
