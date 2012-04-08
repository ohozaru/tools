<?php
class ClixParameter {
    public $name;
    public $value;
    public $keyword;

    public function __construct($parameter) {
        $this->parse($parameter);
    }

    protected function parse($parameter) {

        /* all parameters begining wiht double - */
        if(strpos($parameter, '--') !== false)
        {
            $this->setName($parameter);
            $this->value = true;
            return;
        }

        /* all parameters in format name=value, name="value" */
        if($values = explode('=', $parameter))
        {
            $this->setName(array_shift($values));
            $this->value = implode('=',$values);
            return;
        }

        throw new InvalidArgumentException('Unkown parameter type: '.$parameter);
    }

    public function setName($name) {

        $namePattern = '/[^.a-z0-9_-]+/i';

        if(preg_match($namePattern, $name))
            throw new InvalidArgumentException('Invalid character in argument');

        $this->keyword = $name;

        if(substr($name,0,2) == '--')
            $name = ltrim($name, '-');

        $this->name = $name;
    }
}
