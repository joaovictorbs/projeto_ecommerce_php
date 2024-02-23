<?php

namespace Joaovictorbs\Model;

use \Joaovictorbs\DB\Sql;
use \Joaovictorbs\Model;

Class Product extends Model{


    public static function listAll()    # lista todas as categorieas
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
    }


    public static function checkList($list) # recebe lista de produtos
    {
        foreach ($list as &$row) {
            $p = new Product();
            $p->setData($row);
            $row = $p->getValues();
        }

        return $list;
    }


    public function save()    # salva nova categoria
    {
        $sql = new Sql();

        # chama procedure com dados já setados
        $results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", array(
            ":idproduct"=>$this->getidproduct(),
            ":desproduct"=>$this->getdesproduct(),
            ":vlprice"=>$this->getvlprice(),
            ":vlwidth"=>$this->getvlwidth(),
            ":vlheight"=>$this->getvlheight(),
            ":vllength"=>$this->getvllength(),
            ":vlweight"=>$this->getvlweight(),
            ":desurl"=>$this->getdesurl()
        ));

        $this->setData($results[0]); # grava dados da nova categoria

    }


    public function get($idproduct) # carrega dados e realiza o set
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", array(
            ":idproduct"=>$idproduct
        ));

        $this->setData($results[0]);
    }


    public function delete() # apaga categoria
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", array(
            "idproduct"=>$this->getidproduct()
        ));

    }


    public function checkPhoto() # verifica se produto possui foto
    {
        if (file_exists
            ($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .  # caminho pasta sistema operacinal
                "res" . DIRECTORY_SEPARATOR .
                "site" . DIRECTORY_SEPARATOR .
                "img" . DIRECTORY_SEPARATOR .
                "products" . DIRECTORY_SEPARATOR .
                $this->getidproduct() . ".jpg"
                )) {
                 
                $url = "/res/site/img/products/" . $this->getidproduct() . ".jpg"; # url de foto
        }
        else {
            $url = "/res/site/img/product.jpg"; # foto padrao
        }

        return $this->setdesphoto($url);
    }


    public function getValues()
    {
        $this->checkPhoto(); # verifica se produto tem foto

        $values = parent::getValues(); # classe mae

        return $values;

    }


    public function setPhoto($file)
    {
        $extension = explode('.', $file['name']);  # pega extensao do arquivo
        $extension = end($extension);

        switch($extension) {
            case "jpg":
            case "jpeg":
            $image = imagecreatefromjpeg($file["tmp_name"]);
            break;

            case "gif":
            $image = imagecreatefromgif($file["tmp_name"]);
            break;

            case "png":
            $image = imagecreatefrompng($file["tmp_name"]);
            break;
        }

        $dist = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .  # caminho pasta sistema operacinal
                "res" . DIRECTORY_SEPARATOR .
                "site" . DIRECTORY_SEPARATOR .
                "img" . DIRECTORY_SEPARATOR .
                "products" . DIRECTORY_SEPARATOR .
                $this->getidproduct() . ".jpg";

        imagejpeg($image, $dist); # salva imagem

        imagedestroy($image); # destroi imagem da memoria

        $this->checkPhoto();
    }


    public function getFromURL($desurl)
    {
        $sql = new Sql();

        $rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", [
            ':desurl'=>$desurl
        ]);

        $this->setData($rows[0]);
    }


    public function getCategories()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_categories a INNER JOIN tb_productscategories b on A.idcategory = b.idcategory WHERE b.idproduct = :idproduct", [
            ':idproduct'=>$this->getidproductadmi()
        ]);
    }


    public static function getPage($page = 1, $itemsPerPage = 10)
    {
        $start = ($page - 1) * $itemsPerPage;
        
        $sql = new Sql();

        $results = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_products
            ORDER BY desproduct
            LIMIT $start, $itemsPerPage;
        ");

        $resultTotal = $sql->select("SELECT FOUND_ROWS() as nrtotal;");

        return [
            'data'=>$results,
            'total'=>(int)$resultTotal[0]['nrtotal'],
            'pages'=>ceil($resultTotal[0]['nrtotal'] / $itemsPerPage)
        ];
    }


    public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
    {
        $start = ($page - 1) * $itemsPerPage;
        
        $sql = new Sql();

        $results = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_products
            WHERE desproduct LIKE :search
            ORDER BY desproduct
            LIMIT $start, $itemsPerPage;
        ", [
            'search'=>'%'.$search.'%'
        ]);

        $resultTotal = $sql->select("SELECT FOUND_ROWS() as nrtotal;");

        return [
            'data'=>$results,
            'total'=>(int)$resultTotal[0]['nrtotal'],
            'pages'=>ceil($resultTotal[0]['nrtotal'] / $itemsPerPage)
        ];
    }

}

?>