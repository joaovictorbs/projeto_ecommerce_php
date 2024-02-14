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


    public function getProducts($related = true) # retorna produtos relacionados ou nao da categoria
    {
        $sql = new Sql;

        if ($related === true) {
            return $sql->select("SELECT * FROM tb_products 
                          WHERE idproduct IN (
                            SELECT a.idproduct 
                            FROM tb_products a
                            INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
                            WHERE b.idcategory = :idcategory
                          );"
                        , [
                            ":idcategory"=>$this->getidcategory()
                        ]);
        }
        else {
            return $sql->select("SELECT * FROM tb_products 
                          WHERE idproduct NOT IN (
                            SELECT a.idproduct 
                            FROM tb_products a
                            INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
                            WHERE b.idcategory = :idcategory
                          );"
                        , [
                            ":idcategory"=>$this->getidcategory()
                        ]);
        }
        
    }


    public function addProduct(Product $product)  # sera do tipo classe produto
    {
        $sql = new Sql();

        $sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES(:idcategory, :idproduct)", [
            ':idcategory'=>$this->getidcategory(),
            ':idproduct'=>$product->getidproduct()
        ]);
    }


    public function removeProduct(Product $product)  # sera do tipo classe produto
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", [
            ':idcategory'=>$this->getidcategory(),
            ':idproduct'=>$product->getidproduct()
        ]);
    }


    public function getProductsPage($page = 1, $itemsPerPage = 3)
    {
        $start = ($page - 1) * $itemsPerPage;
        
        $sql = new Sql();

        $results = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_products a
            INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
            INNER JOIN tb_categories c ON c.idcategory = b.idcategory
            WHERE c.idcategory = :idcategory
            LIMIT $start, $itemsPerPage;
        ", [
            ':idcategory'=>$this->getidcategory()
        ]);

        $resultTotal = $sql->select("SELECT FOUND_ROWS() as nrtotal;");

        return [
            'data'=>Product::checkList($results),
            'total'=>(int)$resultTotal[0]['nrtotal'],
            'pages'=>ceil($resultTotal[0]['nrtotal'] / $itemsPerPage) # converte arredondando para cima
        ];
    }

}

?>