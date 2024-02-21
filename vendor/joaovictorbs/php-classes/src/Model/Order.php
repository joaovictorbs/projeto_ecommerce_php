<?php

namespace Joaovictorbs\Model;

use \Joaovictorbs\DB\Sql;
use \Joaovictorbs\Model;
use \Joaovictorbs\Model\Cart;

Class Order extends Model {

    const SESSION_SUCCESS   = "OrderSuccess";
    const SESSION_ERROR     = "OrderError";


    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", [
            ':idorder'=>$this->getidorder(),
            ':idcart'=>$this->getidcart(),
            ':iduser'=>$this->getiduser(),
            ':idstatus'=>$this->getidstatus(),
            ':idaddress'=>$this->getidaddress(),
            ':vltotal'=>$this->getvltotal()
        ]);

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }


    public function get($idorder)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_orders a 
                                   INNER JOIN tb_ordersstatus b ON a.idstatus = b.idstatus  
                                   INNER JOIN tb_carts c ON a.idcart = c.idcart 
                                   INNER JOIN tb_users d ON a.iduser = d.iduser
                                   INNER JOIN tb_addresses e ON a.idaddress = e.idaddress
                                   INNER JOIN tb_persons f ON d.idperson = f.idperson
                                   WHERE a.idorder = :idorder
                               ", [
                                   ':idorder'=>$idorder
                               ]);
        
        if (count($results) > 0) {
            $this->setData($results[0]);
        }
         
    }
 

    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_orders a 
                                INNER JOIN tb_ordersstatus b ON a.idstatus = b.idstatus  
                                INNER JOIN tb_carts c ON a.idcart = c.idcart 
                                INNER JOIN tb_users d ON a.iduser = d.iduser
                                INNER JOIN tb_addresses e ON a.idaddress = e.idaddress
                                INNER JOIN tb_persons f ON d.idperson = f.idperson
                                ORDER BY a.dtregister DESC
                            ");
    }


    public function delete()
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_orders WHERE idorder = :idorder", [
            ':idorder'=>$this->getidorder()
        ]);
    }


    public function getCart()
    {
        $cart = new Cart();

        $cart->get((int)$this->getidcart());

        return $cart;
    }

    
    public static function setMsgSuccess($msg)
    {
        $_SESSION[Order::SESSION_SUCCESS] = $msg;
    }
 
 
    public static function getMsgSuccess()
    {
        $msg = (isset($_SESSION[Order::SESSION_SUCCESS])) ? $_SESSION[Order::SESSION_SUCCESS] : "";
 
        Order::clearMsgError();
 
        return $msg;
    }
 
 
    public static function clearMsgSuccess()
    {
        $_SESSION[Order::SESSION_SUCCESS] = NULL;
    }
    
    
    public static function setMsgError($msg)
    {
        $_SESSION[Order::SESSION_ERROR] = $msg;
    }
 
 
    public static function getMsgError()
    {
        $msg = (isset($_SESSION[Order::SESSION_ERROR])) ? $_SESSION[Order::SESSION_ERROR] : "";
 
        Order::clearMsgError();
 
        return $msg;
    }
 
 
    public static function clearMsgError()
    {
        $_SESSION[Order::SESSION_ERROR] = NULL;
    }
}

?>