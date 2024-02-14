<?php

use \Joaovictorbs\Page;


$app->get('/', function() {
    
	$page = new Page(); # chama construtor / cria header

	$page->setTpl("index"); # passa template de html

	# acaba chamando o construtor e criando o footer
});


?>