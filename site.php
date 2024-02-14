<?php

use \Joaovictorbs\Page;
use \Joaovictorbs\Model\Product;
use \Joaovictorbs\Model\Category;

$app->get('/', function() {
    
	$products = Product::listAll();

	$page = new Page(); # chama construtor / cria header

	$page->setTpl("index", [
		'products'=>Product::checklist($products)
	]); # passa template de html

	# acaba chamando o construtor e criando o footer
});

$app->get("/categories/:idcategory", function($idcategory){

	$category = new Category();

	$category->get((int)$idcategory);

	$page = new Page();

	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>Product::checkList($category->getProducts())
	]);

});

?>