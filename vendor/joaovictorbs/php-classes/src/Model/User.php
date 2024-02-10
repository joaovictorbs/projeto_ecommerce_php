<?php

namespace Joaovictorbs\Model;

use \Joaovictorbs\DB\Sql;
use \Joaovictorbs\Model;

Class User extends Model{

    const SESSION = "User"; # constante para sessao dos dados

    public static function login($login, $password) 
    {
        
        // var_dump("teste");die;
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


}

?>