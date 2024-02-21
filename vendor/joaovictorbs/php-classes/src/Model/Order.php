<?php

namespace Joaovictorbs\Model;

use \Joaovictorbs\DB\Sql;
use \Joaovictorbs\Model;

Class Order extends Model {


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
 
}

?>