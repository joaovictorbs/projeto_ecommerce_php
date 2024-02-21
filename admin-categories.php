<?php

use \Joaovictorbs\Page;
use \Joaovictorbs\PageAdmin;
use \Joaovictorbs\Model\User;
use \Joaovictorbs\Model\Category;
use Joaovictorbs\Model\Product;

$app->get("/admin/categories", function(){
	
	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$pagination = Category::getPage($page);

	if ($search != '') {
		$pagination = Category::getPageSearch($search, $page);
	}

	$pages = [];

	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, [
			'href'=>'/admin/categories?'.http_build_query([
				'page'=>$x+1,
				'search'=>$search
			]),
			'text'=>$x+1
		]);
	}

	$categories = Category::listAll();

	$page = new PageAdmin();

	$page->setTpl("categories", array(
		"categories"=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages
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


$app->get("/admin/categories/:idcategory/products", function($idcategory){
	
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$page = new PageAdmin();

	$page->setTpl("categories-products", [
		'category'=>$category->getValues(),
		'productsRelated'=>$category->getProducts(),
		'productsNotRelated'=>$category->getProducts(false)
	]);

});


$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct){
	
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$product = new Product();

	$product->get((int)$idproduct);

	$category->addProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;

});


$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct){
	
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$product = new Product();

	$product->get((int)$idproduct);

	$category->removeProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;

});

?>