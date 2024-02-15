<?php

namespace Joaovictorbs\Model;

use Exception;
use \Joaovictorbs\DB\Sql;
use \Joaovictorbs\Model;
use \Joaovictorbs\Product;
use \Joaovictorbs\Model\User;

Class Cart extends Model{

    const SESSION = "Cart"; # sessao associada ao carrinho

    public static function getFromSession() # verifica se existe o carrinho ou nao
    {
        $cart = new Cart();

        if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) { # verifica se carrinho existe
            $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
        }
        else {
            $cart->getFromSessionId(); # busca carrinho pelo id de sessao

            if(!(int)$cart->getidcart() > 0) { 
                
                $data = [
                    'dessessionid'=>session_id()
                ];

                if (User::checkLogin(false)) {
                    $user = User::getFromSession(); # verifica existencia de usuario

                    $data['iduser'] = $user->getiduser();
                }

                $cart->setData($data);

                $cart->save();
                
                $cart->setToSession();

            }
        } 
        
        return $cart;
    }


    public function save()    # salva nova categoria
    {
        $sql = new Sql();

        # chama procedure com dados já setados
        $results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", array(
            ":idcart"=>$this->getidcart(),
            ":dessessionid"=>$this->getdessessionid(),
            ":iduser"=>$this->getiduser(),
            ":deszipcode"=>$this->getdeszipcode(),
            ":vlfreight"=>$this->getvlfreight(),
            ":nrdays"=>$this->getnrdays(),
        ));

        $this->setData($results[0]); # grava dados da nova categoria

    }


    public function getFromSessionId() # carrega dados e realiza o set
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", array(
            ":dessessionid"=>session_id()
        ));

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
        
    }


    public function get(int $idcart) # carrega dados e realiza o set
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", array(
            ":idcart"=>$idcart
        ));

        if (count($results) > 0) {
            $this->setData($results[0]);
        }    
    }


    public function setToSession() # coloca carrinho na sessao
    {
        $_SESSION[Cart::SESSION] = $this->getValues();
    }


}

?>