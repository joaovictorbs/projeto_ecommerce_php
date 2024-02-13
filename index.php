<?php 

session_start();
require_once("vendor/autoload.php");

use \Slim\Slim;
use \Joaovictorbs\Page;
use \Joaovictorbs\PageAdmin;
use \Joaovictorbs\Model\User;
use \Joaovictorbs\Model\Category;

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


$app->get('/admin/users', function() { # lista usuarios
    
	User::verifyLogin();

	$users = User::listAll();

	$page = new PageAdmin();

	$page->setTpl("users", array (
		"users"=>$users
	));

});


$app->get('/admin/users/create', function() { # cria novo usuario
    
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("users-create");

});


$app->get('/admin/users/:iduser/delete', function($iduser) { # exclui usuario
    
	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$user->delete();

	header("Location: /admin/users");
	exit;
});


$app->get('/admin/users/:iduser', function($iduser) { # atualizar usuario / passa id
    
	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-update", array(
		"user"=>$user->getValues() # pega todos valores do usuario
	));

});


$app->post('/admin/users/create', function() { # recebe os dados para criar usuario
    
	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

	$user->setData($_POST); # cria um atributo para cada valor do post

	$user->save(); # executa insert dentro do banco

	header("Location: /admin/users");
	exit;
});


$app->post('/admin/users/:iduser', function($iduser) {
    
	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

	$user->get((int)$iduser);

	$user->setData($_POST);

	$user->update();

	header("Location: /admin/users");
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


$app->get("/admin/categories", function(){
	
	User::verifyLogin();

	$categories = Category::listAll();

	$page = new PageAdmin();

	$page->setTpl("categories", array(
		"categories"=>$categories
	));

});


$app->get("/admin/categories/create", function(){
	
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("categories-create");

});


$app->post("/admin/categories/create", function(){
	
	User::verifyLogin();

	$category = new Category();

	$category->setData($_POST); # instancia da classe model

	$category->save();
	
	header("Location: /admin/categories");
	exit;

});


$app->get("/admin/categories/:idcategory/delete", function($idcategory){
	
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory); # busca dado antes de deletar

	$category->delete();

	header("Location: /admin/categories");
	exit;
});


$app->get("/admin/categories/:idcategory", function($idcategory){
	
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$page = new PageAdmin();

	$page->setTpl("categories-update", [
		"category"=>$category->getValues()
	]);
});


$app->post("/admin/categories/:idcategory", function($idcategory){ # realiza update da categoria
	
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$category->setData($_POST);

	$category->save();
	
	header("Location: /admin/categories");
	exit;

});


$app->run();

 ?>