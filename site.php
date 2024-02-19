<?php

use \Joaovictorbs\Page;
use \Joaovictorbs\Model\Product;
use \Joaovictorbs\Model\Category;
use \Joaovictorbs\Model\Cart;
use \Joaovictorbs\Model\Address;
use \Joaovictorbs\Model\User;

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


$app->get("/checkout", function(){ # so realiza checkout da compra se estiver logado

	User::verifyLogin(false);

	$cart = Cart::getFromSession();

	$address = new Address();

	$page = new Page();

	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
	]);
});


$app->get("/login", function(){ # tela de login

	$page = new Page();

	$page->setTpl("login", [
		'error'=>User::getError(),
		'errorRegister'=>User::getErrorRegister(),
		'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] 
		: ['name'=>'', 'email'=>'', 'phone'=>'']
	]);

});


$app->post("/login", function(){

	try {

		User::login($_POST['login'], $_POST['password']);

	} catch(Exception $e) {

		User::setError($e->getMessage());

	}

	header("Location: /checkout");
	exit;

});


$app->get("/logout", function(){ # realiza logout

	User::logout();

	header("Location: /login");
	exit;

});


$app->post("/register", function(){ # registra novo usuario


	$_SESSION['registerValues'] = $_POST; # todos os dados que receber coloca na sessao para nao perder eles

	if(!isset($_POST['name']) || $_POST['name'] == '') {
		User::setErrorRegister("Por favor, preencha o seu nome.");
		
		header("Location: /login");
		exit;
	}

	if(!isset($_POST['email']) || $_POST['email'] == '') {
		User::setErrorRegister("Por favor, preencha o seu e-mail.");
		
		header("Location: /login");
		exit;
	}

	if(User::checkLoginExist($_POST['email']) === true) {
		User::setErrorRegister("Endereço de e-mail já existente.");
		
		header("Location: /login");
		exit;
	}

	if(!isset($_POST['password']) || $_POST['password'] == '') {
		User::setErrorRegister("Por favor, preencha a sua senha.");
		
		header("Location: /login");
		exit;
	}

	$user = new User();

	$user->setData([
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrphone'=>$_POST['phone']
	]);

	$user->save();

	User::login($_POST['email'], $_POST['password']); # autentica usuario

	header("Location: /checkout");
	exit;

});


?>