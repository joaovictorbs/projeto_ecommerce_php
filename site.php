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

	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);

});


$app->get("/cart/:idproduct/add", function($idproduct){

	$product = new Product;

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1; # verifica se ja vem com mais de 1 quantidade

	for ($i = 0; $i < $qtd; $i++){
		$cart->addProduct($product); # adiciona produto no carrinho
	}

	header("Location: /cart");
	exit;
});


$app->get("/cart/:idproduct/minus", function($idproduct){

	$product = new Product;

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product); # remove uma quantidade de produto do carrinho

	header("Location: /cart");
	exit;
});


$app->get("/cart/:idproduct/remove", function($idproduct){

	$product = new Product;

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product, true); # remove produto do carrinho

	header("Location: /cart");
	exit;
});


$app->post("/cart/freight", function(){

	$cart = Cart::getFromSession(); # recupera sessao do carrinho

	$cart->setFreight($_POST['zipcode']); # envia cep

	header("Location: /cart");
	exit;
});

?>