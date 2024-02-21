<?php

namespace Joaovictorbs\Model;

use \Joaovictorbs\DB\Sql;
use \Joaovictorbs\Model;

Class OrderStatus extends Model {

    const EM_ABERTO             = 1;
    const AGUARDANDO_PAGEMENTO  = 2;
    const PAGO                  = 3;
    const ENTREGUE              = 4;


    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_ordersstatus ORDER BY desstatus");
    }
}

?>