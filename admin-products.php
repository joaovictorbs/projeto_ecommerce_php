<?php

use Joaovictorbs\PageAdmin;
use \Joaovictorbs\Model\User;
use Joaovictorbs\Model\Product;

$app->get("/admin/products", function(){
    
    User::verifyLogin();
    
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page   = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$pagination = Product::getPage($page);

	if ($search != '') {
		$pagination = Product::getPageSearch($search, $page);
	}

	$pages = [];

	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, [
			'href'=>'/admin/products?'.http_build_query([
				'page'=>$x+1,
				'search'=>$search
			]),
			'text'=>$x+1
		]);
	}

    $page = new PageAdmin();

    $page->setTpl("products", [
        "products"=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages
    ]);

});


$app->get("/admin/products/create", function(){
    
    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("products-create");
	
});


$app->post("/admin/products/create", function(){
    
    User::verifyLogin();

    $product = new Product();

    $product->setData($_POST); # define set

    $product->save(); # salva os produtos

    header("Location: /admin/products");
    exit;
    
});


$app->get("/admin/products/:idproduct", function($idproduct){
    
    User::verifyLogin();

    $product = new Product();
	
    $product->get((int)$idproduct);

	$page = new PageAdmin();

	$page->setTpl("products-update", [
		"product"=>$product->getValues()
	]);
    
});


$app->post("/admin/products/:idproduct", function($idproduct){ # realiza update da categoria
	
	User::verifyLogin();

    $product = new Product();

    $product->get((int)$idproduct);

	$product->setData($_POST);

	$product->save();
	
    $product->setPhoto($_FILES["file"]); # adiciona foto

	header("Location: /admin/products");
	exit;

});


$app->get("/admin/products/:idproduct/delete", function($idproduct){
    
    User::verifyLogin();

    $product = new Product();
	
    $product->get((int)$idproduct);

	$product->delete();
    
    header("Location: /admin/products");
    exit;

});



?>