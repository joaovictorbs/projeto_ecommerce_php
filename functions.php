<?php

function formatPrice($vlprice)
{

    if ($vlprice === NULL) $value = 0;

    $value = number_format($vlprice, 2, ",", ".");
        
    return $value;
}

?>