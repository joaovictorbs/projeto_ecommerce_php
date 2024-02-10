<?php 

session_start();
require_once("vendor/autoload.php");

use \Slim\Slim;
use \Joaovictorbs\Page;
use \Joaovictorbs\PageAdmin;
use \Joaovictorbs\Model\User;

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {
    
	$page = new Page(); # chama construtor / cria header

	$page->setTpl("index"); # passa template de html

	# acaba chamando o construtor e criando o footer
});


$app->get('/admin', function() { # acesso ao admin
    
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("index"); # nao identifica que é o index admin
});


$app->get('/admin/login', function() { # acesso ao login do admin
    
	$page = new PageAdmin([  # desabilita header e footer
		"header"=>false,
		"footer"=>false,
	]);

	$page->setTpl("login");
});


$app->post('/admin/login', function() { # valida login
    
	User::login($_POST["login"], $_POST["password"]);

	header("Location: /admin");
	exit;

});


$app->get('/admin/logout', function() {
    
	User::logout();

	header("Location: /admin/login");
	exit;

});

$app->run();

 ?>