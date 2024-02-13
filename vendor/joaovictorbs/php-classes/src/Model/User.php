<?php

namespace Joaovictorbs\Model;

use \Joaovictorbs\DB\Sql;
use \Joaovictorbs\Model;

Class User extends Model{

    const SESSION = "User"; # constante para sessao dos dados

    public static function login($login, $password) 
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array( # busca login
            ":LOGIN"=>$login
        ));

        if (count($results) === 0) {   # se encontrou login
            throw new \Exception("Usuário inexistente ou senha inválida.", 1);
        }

        $data = $results[0]; # dados do usuario no primeiro registro encontrado

        if (password_verify($password, $data["despassword"]) === true) { # retorna se hash é igual a senha
            $user = new User();

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


    public static function verifyLogin($inadmin = true)    # verifica se existe usuario autenticado / se é usuario admin
    {
        if (            
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
            ||
            (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin            
        ) {
            header("Location: /admin/login");
            exit;
        }
    }


    public static function listAll()    # lista todos os usuarios
    {
        $sql =  new Sql();

        return $sql->select("SELECT * FROM tb_users a JOIN tb_persons b USING(idperson) order by b.desperson");
    }


    public function save()    # salva dados do banco
    {
        $sql =  new Sql();

        # chama procedure com dados já setados
        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
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

        $this->setData($results[0]);
    }


    public function update()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
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
}

?>