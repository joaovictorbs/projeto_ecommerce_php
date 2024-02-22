<?php

use \Joaovictorbs\PageAdmin;
use \Joaovictorbs\Model\User;
use \Joaovictorbs\Model\Order;
use Joaovictorbs\Model\OrderStatus;

$app->get("/admin/orders/:idorder/status", function($idorder) {
    
    User::verifyLogin();

    $order = new Order();

    $order->get((int)$idorder);

    $page = new PageAdmin();
    
    $page->setTpl("/admin/order-status", [
        "order"=>$order->getValues(),
        "status"=>OrderStatus::listAll(),
        "msgSuccess"=>Order::getMsgSuccess(),
        "msgError"=>Order::getMsgError()
    ]);

});


$app->post("/admin/orders/:idorder/status", function($idorder) { # altera status do pedido
    
    User::verifyLogin();

    if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
        Order::setMsgError("Informe o status atual.");
        header("Location: /admin/orders/" . $idorder . "/status");
        exit;
    }

    $order = new Order();

    $order->get((int)$idorder);

    $order->setidstatus((int)$_POST['idstatus']);

    $order->save();

    Order::setMsgSuccess("Status atualizado com sucesso!");

    header("Location: /admin/orders/" . $idorder . "/status");
    exit;

});


$app->get("/admin/orders/:idorder/delete", function($idorder) { # exclui pedido
    
    User::verifyLogin();

    $order = new Order();

    $order->get((int)$idorder);

    $order->delete();
    
    header("Location: /admin/orders");
    exit;

});


$app->get("/admin/orders/:idorder", function($idorder) { # detalhe do pedido
    
    User::verifyLogin();

    $order = new Order();

    $order->get((int)$idorder);

    $cart = $order->getCart(); # pega carrinho do pedido

    $page = new PageAdmin();
    
    $page->setTpl("/admin/order", [
        "order"=>$order->getValues(),
        "cart"=>$cart->getValues(),
        "products"=>$cart->getProducts()
    ]);

});

$app->get("/admin/orders", function() { # lista todos os pedidos
    
    User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page   = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$pagination = Order::getPage($page);

	if ($search != '') {
		$pagination = Order::getPageSearch($search, $page);
	}

	$pages = [];

	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, [
			'href'=>'/admin/orders?'.http_build_query([
				'page'=>$x+1,
				'search'=>$search
			]),
			'text'=>$x+1
		]);
	}

    $page = new PageAdmin();

    $page->setTpl("orders", [
        "orders"=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages    
    ]);

});

?>