<?php

namespace Joaovictorbs;

class Model {
    private $values = [];

    public function __call($name, $args) # toda vez que metodo é chamado / nome do metodo e parametros
    { 
        $method = substr($name, 0, 3); # recupera tipo de metodo / get ou set
        $fieldName = substr($name, 3, strlen($name)); # nome do campo

        switch ($method)
        {
            case "get":
                return (isset($this->values[$fieldName])) ? $this->values[$fieldName] : NULL;
            break;
            case "set":
                $this->values[$fieldName] = $args[0];
            break;
        }
    }


    public function setData($data = array())
    {
        foreach ($data as $key => $value) {
            $this->{"set".$key}($value);     # metodo set 
        }
    }


    public function getValues()
    {
        return $this->values;
    }
}


?>