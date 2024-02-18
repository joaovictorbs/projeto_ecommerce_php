<?php

use \Joaovictorbs\Model\User;

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

?>