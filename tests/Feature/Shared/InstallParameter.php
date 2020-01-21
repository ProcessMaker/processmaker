<?php

namespace Tests\Feature\Shared;

class InstallParameter
{    
    public $flag;
    
    public $env;
    
    public $value;
    
    public function __construct($flag, $env = null, $value = null) {
        $this->flag = $flag;
        $this->env = $env;
        $this->value = ($value !== null) ? $value : true; 
    }
}
