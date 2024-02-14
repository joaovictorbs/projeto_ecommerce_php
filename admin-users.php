<?php

use \Joaovictorbs\PageAdmin;
use \Joaovictorbs\Model\User;


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

?>