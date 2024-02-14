<?php

use \Joaovictorbs\PageAdmin;
use \Joaovictorbs\Model\User;


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

$app->get('/admin/forgot', function() {
    
	$page = new PageAdmin([  # desabilita header e footer
		"header"=>false,
		"footer"=>false,
	]);

	$page->setTpl("forgot");
});


$app->post('/admin/forgot', function() {
    
	$user = User::getForgot($_POST["email"]); # recupera e-mail
	
	header("Location: /admin/forgot/sent");
	exit;
});


$app->get("/admin/forgot/sent", function(){
	
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-sent");
});


$app->get("/admin/forgot/reset", function(){
		
	$user = User::validForgotDecrypt($_GET["code"]);
	
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));
});


$app->post("/admin/forgot/reset", function(){
	
	$forgot = User::validForgotDecrypt($_POST["code"]);
	
	User::setForgotUsed($forgot["idrecovery"]); # verifica se processo de recuperacao ja foi usado

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [ # hash da senha
		"cost"=>12
	]);

	$user->setPassword($password); # salva nova senha

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset-success");
});

?>