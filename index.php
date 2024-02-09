<?php 

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Joaovictorbs\Page;
use \Joaovictorbs\PageAdmin;

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {
    
	$page = new Page(); # chama construtor / cria header

	$page->setTpl("index"); # passa template de html

	# acaba chamando o construtor e criando o footer

});

$app->get('/admin', function() { # acesso ao admin
    
	$page = new PageAdmin();

	$page->setTpl("index");

});

$app->run();

 ?>