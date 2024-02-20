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

	$address = new Address();

	$cart = Cart::getFromSession();

	if (!isset($_GET['zipcode'])) {

		$_GET['zipcode'] = $cart->getdeszipcode();

	}

	if (isset($_GET['zipcode'])) { # verifica se cep foi enviado

		$address->loadFromCEP($_GET['zipcode']);

		$cart->setdeszipcode($_GET['zipcode']);

		$cart->save();

		// $cart->getCalculateTotal();

	}

	if (!$address->getdesaddress()) $address->setdesaddress('');
	if (!$address->getdescomplement()) $address->setdescomplement('');
	if (!$address->getdesdistrict()) $address->setdesdistrict('');
	if (!$address->getdescity()) $address->setdescity('');
	if (!$address->getdesstate()) $address->setdesstate('');
	if (!$address->getdescountry()) $address->setdescountry('');
	if (!$address->getdeszipcode()) $address->setdeszipcode('');

	$page = new Page();

	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Address::getMsgError()
	]);
});


$app->post("/checkout", function(){

	User::verifyLogin(false);

	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
		Address::setMsgError("Por favor, informe o CEP.");
		header("Location: /checkout");
		exit;
	}

	if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
		Address::setMsgError("Por favor, informe o endereço.");
		header("Location: /checkout");
		exit;
	}

	if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
		Address::setMsgError("Por favor, informe o bairro.");
		header("Location: /checkout");
		exit;
	}

	if (!isset($_POST['descity']) || $_POST['descity'] === '') {
		Address::setMsgError("Por favor, informe a cidade.");
		header("Location: /checkout");
		exit;
	}

	if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
		Address::setMsgError("Por favor, informe o estado.");
		header("Location: /checkout");
		exit;
	}

	$user = User::getFromSession();

	$address = new Address();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$address->setData($_POST);

	$address->save();

	header("Location: /order");
	exit;

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


$app->get('/forgot', function() {
    
	$page = new Page();

	$page->setTpl("forgot");
});


$app->post('/forgot', function() {
    
	$user = User::getForgot($_POST["email"], false); # recupera e-mail
	
	header("Location: /forgot/sent");
	exit;
});


$app->get("/forgot/sent", function(){
	
	$page = new Page();

	$page->setTpl("forgot-sent");
});


$app->get("/forgot/reset", function(){
		
	$user = User::validForgotDecrypt($_GET["code"]);
	
	$page = new Page();

	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));
});


$app->post("/forgot/reset", function(){
	
	$forgot = User::validForgotDecrypt($_POST["code"]);
	
	User::setForgotUsed($forgot["idrecovery"]); # verifica se processo de recuperacao ja foi usado

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [ # hash da senha
		"cost"=>12
	]);

	$user->setPassword($password); # salva nova senha

	$page = new Page();

	$page->setTpl("forgot-reset-success");
});


$app->get("/profile", function(){
	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile", [
		'user'=>$user->getValues(),
		'profileMsg'=>User::getSuccess(),
		'profileError'=>User::getError()
	]);
});


$app->post("/profile", function(){
	User::verifyLogin(false);

	$user = User::getFromSession();

	if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
		User::setError("Por favor, preencha o seu nome.");
		header('Location: /profile');
		exit;
	}

	if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
		User::setError("Por favor, preencha o seu e-mail.");
		header('Location: /profile');
		exit;
	}

	if ($_POST['desemail'] !== $user->getdesemail()) {
		if (User::checkLoginExist($_POST['desemail']) === true) {
			User::setError("Endereço de e-mail já existente.");
			header('Location: /profile');
			exit;
		}
	}


	$_POST['inadmin'] 		= $user->getinadmin();
	$_POST['despassword'] 	= $user->getdespassword();
	$_POST['deslogin'] 		= $user->getdeslogin(); # mantem senha, inadmin e email


	$user->setData($_POST);

	$user->update();

	User::setSuccess("Dados alterados com sucesso!");

	header('Location: /profile');
	exit;
});

?>