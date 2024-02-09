<?php

namespace Joaovictorbs;

use Rain\Tpl;

class Page {

    private $tpl;
    private $options = [];
    private $defaults = [
        "data"=>[ ]
    ];

    public function __construct($opts = array())
    {

        $this->options = array_merge($this->defaults, $opts); # caso passe valores, substitue os padroes
        
	    $config = array(
            "tpl_dir"       => $_SERVER["DOCUMENT_ROOT"]."/views/",         # diretorio root do servidor / pasta de htmtl
            "cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache/",   # pasta de cache
            "debug"         => false
       );

        Tpl::configure( $config );
        
        $this->tpl = new Tpl;

        $this->setData($this->options["data"]);
    
        $this->tpl->draw( "header" ); # cria header
    }


    private function setData($data = array()) {   # aplica cada valor recebido ao assign
        foreach ($data as $key => $value) {
            $this->tpl->assign($key, $value);   
        }
    }


    public function setTpl($name, $data = array(), $returnHTML = false) {
        $this->setData($data);

        return $this->tpl->draw($name, $returnHTML);
    }


    public function __destruct()
    {
        $this->tpl->draw("footer"); # cria footer
    }

}


?>
