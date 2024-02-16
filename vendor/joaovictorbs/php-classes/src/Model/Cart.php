<?php

namespace Joaovictorbs\Model;

use Exception;
use \Joaovictorbs\DB\Sql;
use \Joaovictorbs\Model;
use \Joaovictorbs\Model\Product;
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


    public function addProduct(Product $product) # adiciona produto no carrinho / instancia da classe 
    {
        $sql = new Sql();

        $sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES(:idcart, :idproduct)", [
            ':idcart'=>$this->getidcart(),
            ':idproduct'=>$product->getidproduct(),
        ]);
    }


    public function removeProduct(Product $product, $all = false) # atualiza data de remocao do carrinho / remove 1 ou mais itens
    {
        $sql = new Sql();

        if ($all) { # remove todos os produtos
            $sql->query("UPDATE tb_cartsproducts set dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL", [
                ':idcart'=>$this->getidcart(),
                ':idproduct'=>$product->getidproduct(),
            ]);
        }
        else { # remove somente 1 produto
            $sql->query("UPDATE tb_cartsproducts set dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct 
                         AND dtremoved IS NULL LIMIT 1"
            , [
                ':idcart'=>$this->getidcart(),
                ':idproduct'=>$product->getidproduct(),
            ]);
        }

    }


    public function getProducts() # recupera produtos do carrinho
    {
        $sql = new Sql();

        $rows = $sql->select("SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) as nrqtd, SUM(b.vlprice) as vltotal
                             FROM tb_cartsproducts a 
                             INNER JOIN tb_products b ON a.idproduct = b.idproduct 
                             WHERE a.idcart = :idcart 
                             AND a.dtremoved IS NULL 
                             GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl
                             ORDER BY b.desproduct
                           ", [
                            'idcart'=>$this->getidcart()
                           ]);

        return Product::checkList($rows);

    }

}

?>