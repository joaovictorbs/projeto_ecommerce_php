<?php

namespace Joaovictorbs\Model;

use Exception;
use \Joaovictorbs\DB\Sql;
use \Joaovictorbs\Model;
use \Joaovictorbs\Model\Product;
use \Joaovictorbs\Model\User;

Class Cart extends Model{

    const SESSION = "Cart"; # sessao associada ao carrinho
	const SESSION_ERROR = "CartError";


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

        $this->getCalculateTotal(); # toda vez que adiciona um produto recalcula frete
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

        $this->getCalculateTotal(); # toda vez que remove um produto recalcula frete


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


    public function getProductsTotals() # recupera valores total do carrinho
    {
        $sql = new Sql();

        $results = $sql->select("SELECT SUM(vlprice) AS vlprice,
                                        SUM(vlwidth) AS vlwidth,
                                        SUM(vlheight) AS vlheight,
                                        SUM(vllength) AS vllength,
                                        SUM(vlweight) AS vlweight,
                                        COUNT(*) AS nrqtd
                                FROM tb_products a
                                INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
                                WHERE b.idcart = :idcart 
                                AND dtremoved IS NULL;"
                        , [
                            'idcart'=>$this->getidcart()
                        ]);
        
        if (count($results) > 0) {
            return $results[0];
        }
        else{
            return [];
        }
    }


    public function setFreight($nrzipcode) # formata cep
    {
        $nrzipcode = str_replace('-', '', $nrzipcode);

        $totals = $this->getProductsTotals();

        $qs = http_build_query([ # transforma em formato de url
            'ncCdEmpresa'=>'',
            'sDsSenha'=>'',
            'nCdServico'=>'40010',
            'sCepOrigem'=>'58340970',
            'sCepDestino'=>$nrzipcode,
            'nVlPeso'=>$totals['vlweight'],
            'nCdFormato'=>'1',
            'nVlComprimento'=>$totals['vllength'],
            'nVlAltura'=>$totals['vlheight'],
            'nVlLargura'=>$totals['vlwidth'],
            'nVlDiametro'=>'0',
            'sCdMaoPropria'=>'S',
            'nVlValorDeclarado'=>$totals['vlprice'],
            'sCdAvisoRecebimento'=>'S'
        ]);


        if ($totals['nrqtd'] > 0) { # verifica se carrinho possui algum produto
            
            if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;
            if ($totals['vllength'] < 16) $totals['vllength'] = 16;
            
			$xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);

			$result = $xml->Servicos->cServico;

			if ($result->MsgErro != '') {

                Cart::setMsgError((string)$result->MsgErro);

			} else {

				Cart::clearMsgError();

			}

			$this->setnrdays($result->PrazoEntrega);
			$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
			$this->setdeszipcode($nrzipcode);

			$this->save();

			return $result;
		} 
         else {

		}

	}


    public static function formatValueToDecimal($value):float
    {
        $value = str_replace('.', '', $value);
        return str_replace(',', '.', $value);
    } 


    public static function setMsgError($msg) # define mensagem de erro para constante
    {
        $_SESSION[Cart::SESSION_ERROR] = $msg;
    }


    public static function getMsgError() # obtem mensagem de erro
    {
        $msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";

        Cart::clearMsgError();

        return $msg;
    }


    public static function clearMsgError() #limpa mensagem de erro
    {
        $_SESSION[Cart::SESSION_ERROR] = NULL;
    }


    public function updateFreight()
    {
        if ($this->getdeszipcode() != '') {
            $this->setFreight($this->getdeszipcode());
        }
    }


    public function getValues()
    {
        $this->getCalculateTotal(); # calcula total e subtotal

        return parent::getValues();
    }


    public function getCalculateTotal()
    {
        $this->updateFreight();

        $totals = $this->getProductsTotals();

        $this->setvlsubtotal($totals['vlprice']);
        $this->setvltotal($totals['vlprice'] + $this->getvlfreight());
    }

}

?>