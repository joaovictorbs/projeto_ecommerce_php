<?php

namespace Joaovictorbs\Model;

use \Exception;
use \Joaovictorbs\DB\Sql;
use \Joaovictorbs\Model;
use \Joaovictorbs\Mailer;

Class User extends Model{

    const SESSION           = "User"; # constante para sessao dos dados
    const SECRET            = ""; # constante para chave de recuperacao de senha / deve ser 16 bits
	const SECRET_IV         = "";
	const ERROR             = "UserError";
	const ERROR_REGISTER    = "UserErrorRegister";


    public static function getFromSession()
    {
		$user = new User();

		if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {

			$user->setData($_SESSION[User::SESSION]);

		}

		return $user;

    }


    public static function checkLogin($inadmin = true) # verifica se usuario esta logado
    {
        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
        ) {
            return false;
        }
        else {
            if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) { # verifica se usuario é de administracao
                return true;
            }
            else if ($inadmin === false) {
                return true;
            }
            else {
                return false;
            }
        }
    }


    public static function login($login, $password) 
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array( # busca login
            ":LOGIN"=>$login
        ));

        if (count($results) === 0) {   # se encontrou login
            throw new \Exception("Usuário inexistente ou senha inválida.", 1);
        }

        $data = $results[0]; # dados do usuario no primeiro registro encontrado

        if (password_verify($password, $data["despassword"]) === true) { # retorna se hash é igual a senha
            $user = new User();

            $data['desperson'] = utf8_encode($data['desperson']);

            $user->setData($data); # com a quantidade de campos, criamos os atributos de cada campo
        
            $_SESSION[User::SESSION] = $user->getValues(); # colocamos os dados dentro da sessao

            return $user;
        }
        else {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }

    }


    public static function logout()
    {
        $_SESSION[User::SESSION] = NULL;
    }


	public static function verifyLogin($inadmin = true)
	{

		if (!User::checkLogin($inadmin)) {

			if ($inadmin) {
				header("Location: /admin/login");
			} else {
				header("Location: /login");
			}
			exit;

		}

	}


    public static function listAll()    # lista todos os usuarios
    {
        $sql =  new Sql();

        return $sql->select("SELECT * FROM tb_users a JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
    }


    public function save()    # salva dados do banco
    {
        $sql =  new Sql();

        # chama procedure com dados já setados
        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson"=>utf8_decode($this->getdesperson()),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>User::getPasswordhash($this->getdespassword()),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]); # grava dados do novo usuarios
    }


    public function get($iduser)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
            ":iduser"=>$iduser
        ));

        $data = $results[0];

        $data['desperson'] = utf8_encode($data['desperson']);

        $this->setData($data);
    }


    public function update()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>utf8_decode($this->getdesperson()),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>User::getPasswordhash($this->getdespassword()),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);
    }


    public function delete()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_users_delete(:iduser)", array(
            ":iduser"=>$this->getiduser(),
        ));
    }


    public static function getForgot($email, $inadmin = true)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email", array(
            ":email"=>$email
        ));

        if (count($results) === 0) {
            throw new \Exception("Não foi possível recuperar a senha.", 1);
        }
        else {
            $data = $results[0];
            
            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array( # salva nova tentativa de recuperar senha
                ":iduser"=>$data["iduser"],
                ":desip"=>$_SERVER["REMOTE_ADDR"] # recupera ip do usuario
            ));

            if (count($results2) === 0) {
                throw new \Exception("Não foi possível recuperar a senha.", 1);
            }
            else {
                $dataRecovery = $results2[0];

                $code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

				$code = base64_encode($code);

                if ($inadmin === true){
                    $link = "http://www.projetoecommercephp.com.br/admin/forgot/reset?code=$code";
                }
                else {
                    $link = "http://www.projetoecommercephp.com.br/forgot/reset?code=$code";
                }
                
                $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha E-commerce Store", "forgot",
                 array(
                    "name"=>$data["desperson"],
                    "link"=>$link
                ));

                $mailer->send();

                return $data;
            }
        }

    }


    public static function validForgotDecrypt($code) # decodifica codigo de recuperacao de senha
    {
		$code = base64_decode($code);

		$idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_userspasswordsrecoveries a
                                 INNER JOIN tb_users b USING(iduser)
                                 INNER JOIN tb_persons c USING(idperson)
                                 WHERE
                                     a.idrecovery = :idrecovery
                                 AND
                                     a.dtrecovery IS NULL
                                 AND
                                     DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
                                ", array(
                                    ":idrecovery"=>$idrecovery
                                ));

        if (count($results) === 0) {
            throw new \Exception("Não foi possível recuperar a senha.");
        }
        else {
            return $results[0]; # retorna dados do usuario
        }
    }


    public static function setForgotUsed($idrecovery) # atualiza tabela de recuperacao de senha
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
            ":idrecovery"=>$idrecovery
        ));
    }

    public function setPassword($password) # atualiza senha do usuario
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            "password"=>$password,
            "iduser"=>$this->getiduser()
        ));
    }


	public static function setError($msg)
	{

		$_SESSION[User::ERROR] = $msg;

	}

	public static function getError()
	{

		$msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

		User::clearError();

		return $msg;

	}

	public static function clearError()
	{

		$_SESSION[User::ERROR] = NULL;

	}


    public static function setErrorRegister($msg)
    {
        $_SESSION[User::ERROR_REGISTER] = $msg;
    }


	public static function getErrorRegister()
	{

		$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

		User::clearErrorRegister();

		return $msg;

	}

	public static function clearErrorRegister()
	{

		$_SESSION[User::ERROR_REGISTER] = NULL;

	}


    public static function checkLoginExist($login) # verifica se ja existe o login
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users where deslogin = :deslogin", [
            ':deslogin'=>$login
        ]);

        return (count($results) > 0); # se retorna algo é porque existe o login
    }

    
    public static function getPasswordhash($password) # realiza hash da senha
    {
        return password_hash($password, PASSWORD_DEFAULT, [
            'cost'=>12
        ]);
    }

}

?>