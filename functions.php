<?php

use \Joaovictorbs\Model\User;
use \Joaovictorbs\Model\Cart;

function formatPrice($vlprice)
{

    if ($vlprice === NULL) $value = 0;

    $value = number_format($vlprice, 2, ",", ".");
        
    return $value;
}


function checklogin($inadmin = true)
{
    return User::checklogin($inadmin);
}


function getUserName()
{
    $user = User::getFromSession();
    
    return $user->getdesperson();
}


function getCartNrQtd() # pega quantidade de itens no carrinho
{
    $cart = Cart::getFromSession();

    $totals = $cart->getProductsTotals();

    return $totals['nrqtd'];   
}


function getCartVlSubtotal() # pega valor subtotal do carrinho
{
    $cart = Cart::getFromSession();

    $totals = $cart->getProductsTotals();

    return formatPrice($totals['vlprice']);   
}

?>