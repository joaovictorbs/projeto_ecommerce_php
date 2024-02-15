<?php

use \Joaovictorbs\Page;
use \Joaovictorbs\Model\Product;
use \Joaovictorbs\Model\Category;
use \Joaovictorbs\Model\Cart;

$app->get('/', function() {
    
	$products = Product::listAll();

	$page = new Page(); # chama construtor / cria header

	$page->setTpl("index", [
		'products'=>Product::checklist($products)
	]); # passa template de html

	# acaba chamando o construtor e criando o footer
});

$app->get("/categories/:idcategory", function($idcategory){ # paginacao

	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1; #recebe pagina
	
	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($page);

	$pages = [];

	for ($i=1; $i <= $pagination['pages']; $i++) {
		array_push($pages, [
			'link'=>'/categories/'. $category->getidcategory() . '?page=' . $i,
			'page'=>$i
		]);
	}
	$page = new Page();

	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>$pagination["data"],
		'pages'=>$pages
	]);

});


$app->get("/products/:desurl", function($desurl){

	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail", [
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	]);

});


$app->get("/cart", function(){

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart");

});

?>