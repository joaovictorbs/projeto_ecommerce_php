<?php

use \Joaovictorbs\Page;
use \Joaovictorbs\Model\Product;
use \Joaovictorbs\Model\Category;
use \Joaovictorbs\Model\Cart;
use \Joaovictorbs\Model\Address;
use \Joaovictorbs\Model\User;
use \Joaovictorbs\Model\Order;
use Joaovictorbs\Model\OrderStatus;

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
	if (!$address->getdesnumber()) $address->setdesnumber('');
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

	$cart = Cart::getFromSession();

	$cart->getCalculateTotal();

	$order = new Order();

	$order->setData([
		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::EM_ABERTO,
		'vltotal'=>$cart->getvltotal()
	]);

	$order->save(); # recupera id do pedido

	header("Location: /order/".$order->getidorder());
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


$app->get("/order/:idorder", function($idorder){
	
	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$page = new Page();

	$page->setTpl("payment", [
		'order'=>$order->getValues()
	]);

});


$app->get("/boleto/:idorder", function($idorder){
	
	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);


	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento 	= 10;
	$taxa_boleto 					= 5.00;
	$data_venc 						= date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
	
	$valor_cobrado 	= formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado 	= str_replace(".", "", $valor_cobrado);
	$valor_cobrado 	= str_replace(",", ".",$valor_cobrado);
	$valor_boleto	= number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

	$dadosboleto["nosso_numero"] 		= $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] 	= $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] 	= $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] 		= date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] 	= date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = 		$valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] 		= $order->getdesperson();
	$dadosboleto["endereco1"] 	= $order->getdesaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] 	= $order->getdescity() . " - " . $order->getdesstate() . " - " . $order->getdescountry() . " -  CEP: " . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] 	= "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] 	= "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] 	= "";
	$dadosboleto["instrucoes1"] 	= "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] 	= "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] 	= "- Em caso de dúvidas entre em contato conosco: joaovictorbsempresa@gmail.com";
	$dadosboleto["instrucoes4"] 	= "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] 		= "";
	$dadosboleto["valor_unitario"] 	= "";
	$dadosboleto["aceite"] 			= "";		
	$dadosboleto["especie"] 		= "R$";
	$dadosboleto["especie_doc"] 	= "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] 	= "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] 		= "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] 	= "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] 	= "E-commerce Store";
	$dadosboleto["cpf_cnpj"] 		= "24.700.731/0001-08";
	$dadosboleto["endereco"] 		= "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] 		= "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] 		= "E-COMMERCE STORE LTDA - ME";

	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;

	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");


	$page = new Page();

	$page->setTpl("payment", [
		'order'=>$order->getValues()
	]);
	
});


$app->get("/profile/orders", function(){ # lista pedidos

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile-orders", [
		'orders'=>$user->getOrders()
	]);

});


$app->get("/profile/orders/:idorder", function($idorder){ # recupera informacao do pedido

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = new Cart();

	$cart->get((int)$order->getidcart());

	$cart->getCalculateTotal();

	$page = new Page();

	$page->setTpl("profile-orders-detail", [
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);

});


$app->get("/profile/change-password", function(){
	
	User::verifyLogin(false);

	$page = new Page();

	$page->setTpl("profile-change-password", [
		'changePassError'=>User::getError(),
		'changePassSuccess'=>User::getSuccess()
	]);

});


$app->post("/profile/change-password", function(){ # altera senha
	
	User::verifyLogin(false);

	if (!isset($_POST['current_pass']) || $_POST['current_pass'] === '') { # verifica senha atual
		User::setError("Informe a senha atual.");
		header("Location: /profile/change-password");
		exit;
	}

	if (!isset($_POST['new_pass']) || $_POST['new_pass'] === '') { # verifica senha atual
		User::setError("Informe a nova senha.");
		header("Location: /profile/change-password");
		exit;
	}

	if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === '') { # verifica senha atual
		User::setError("Confirme a nova senha.");
		header("Location: /profile/change-password");
		exit;
	}

	if ($_POST['new_pass'] !== $_POST['new_pass_confirm']) { # verifica senha atual
		User::setError("Nova senha precisa ser igual com a confirmação.");
		header("Location: /profile/change-password");
		exit;
	}

	if ($_POST['current_pass'] === $_POST['new_pass']) {
		User::setError("É necessário informar uma senha diferente da atual.");
		header("Location: /profile/change-password");
		exit;
	}

	$user = User::getFromSession();

	if (!password_verify($_POST['current_pass'], $user->getdespassword())) { # verifica senha atual do usuario
		User::setError("Senha atual inválida.");
		header("Location: /profile/change-password");
		exit;
	}

	$user->setdespassword($_POST['new_pass']); # define nova senha

	$user->update(); # realiza update da senha

	User::setSuccess("Senha alterada com sucesso!");
	header("Location: /profile/change-password");
	exit;
	
});


?>