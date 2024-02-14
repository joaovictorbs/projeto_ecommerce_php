<?php

namespace Joaovictorbs\Model;

use Exception;
use \Joaovictorbs\DB\Sql;
use \Joaovictorbs\Model;
use \Joaovictorbs\Mailer;

Class Category extends Model{


    public static function listAll()    # lista todas as categorieas
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
    }


    public function save()    # salva nova categoria
    {
        $sql = new Sql();

        # chama procedure com dados já setados
        $results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
            ":idcategory"=>$this->getidcategory(),
            ":descategory"=>$this->getdescategory()
        ));

        $this->setData($results[0]); # grava dados da nova categoria

        Category::updateFile();
    }


    public function get($idcategory) # carrega dados e realiza o set
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", array(
            ":idcategory"=>$idcategory
        ));

        $this->setData($results[0]);
    }


    public function delete() # apaga categoria
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", array(
            "idcategory"=>$this->getidcategory()
        ));

        Category::updateFile();
    }


    public static function updateFile() # atualiza lista de categorias no site
    {
        $categories = Category::listAll();

        $html = [];

        foreach ($categories as $row) {
            array_push($html, '<li><a href="/categories/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>');
        }

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode('', $html));
    }

}

?>