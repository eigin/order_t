<?php
/**
 *  Класс модели пользователей
 *  @author Eigin <sergei@eigin.net>
 *  @version 1.0
 */

namespace model;

use control\SqlBuild;


class User extends Base
{
    /**
     * установить имя таблицы для базовых функций работы с БД
     */
    function __construct ()
    {
        parent::__construct('t_user');
    }


    /**
     * авторизация пользователя
     */
    public function login (array $param)
    {
        $login    = $this->__stringCheck($param['login']);
        $password = $this->__stringCheck($param['password']);
        $userData = $this->getByField(['login'=>$login ]);  
        if (empty($userData[0]['password']))
            return 'Login not registered';
        if ($userData[0]['password'] == md5(md5($password))) {
            $_SESSION['login'] = $userData[0]['login']; 
            $_SESSION['id'] = $userData[0]['id_user'];
            return 'Successfully logged in';
        }
        return 'Wrong password';
    }


    /**
     * добавить нового пользователя
     */     
    public function add (array $param)
    {
        $login     = $this->__stringCheck($param['login']);
        $name_user = $this->__stringCheck($param['name_user']);
        $userData  = $this->getByField(['login'=>$login ]);
        if (!empty($userData[0]['id_user']))
            return 'Sorry, login alredy exist';
        $password = '';
        $password = $this->__generate_password(6);
        $md5pass  = md5(md5($password));
        $response = $this->add(['login'=>$login, 'name_user'=>$name_user, 'password'=>$md5pass ]);  
        $this->__sendMail(['login'=>$param['login'], 'password'=>$password ]);
        return $response;
    }


    /**
     * восстановить пароль
     */     
    public function restorePassword (array $param)
    {
        $login     = $this->__stringCheck($param['login']);
        $userData  = $this->getByField(['login'=>$login ]);
        if (empty($userData[0]['password']))
            return 'Login not registered';
        $password = '';
        $password = $this->__generate_password(6);
        $md5pass  = md5(md5($password));
        $response = $this->edit(['id_user'=>$userData[0]['id_user'], 'password'=>$md5pass ]);
        $this->__sendMail(['login'=>$param['login'], 'password'=>$password ]);
        return 'New password sent to your email';
    }


    /**
     * проверить введенную строку на нежелательные символы
     */     
    private function __stringCheck (string $string)
    {
        $string = stripslashes($string);
        $string = htmlspecialchars($string);
        $string = trim($string);
        return $string;
    }


    /**
     * сгенерировать пароль заданной длины,
     * используя буквенно-числовой набор символов
     */         
    private function __generate_password (int $number)
    {
        $password = '';
        $arr = array('a','b','c','d','e','f',
                     'g','h','i','j','k','l',
                     'm','n','o','p','r','s',
                     't','u','v','x','y','z',
                     'A','B','C','D','E','F',
                     'G','H','I','J','K','L',
                     'M','N','O','P','R','S',
                     'T','U','V','X','Y','Z',
                     '1','2','3','4','5','6',
                     '7','8','9','0');
        for($i = 0; $i < $number; $i++) {
            $index = rand(0, count($arr) - 1);
            $password .= $arr[$index];
        }
        return $password;
    }
    

    /**
     * отправить письмо с логином и паролем для авторизации
     */             
    private function __sendMail (array $param)
    {
        $message   = '<html><head></head><body>Hi.<br>Your login details:<br><br>';
        $message  .= 'Login - ' . $param['login'] . '<br><br>Password - ' . $param['password'] . '</body></html>';
        $address   = $param['login'];
        $subject   = 'Your login details';
        $send      = mail ($address, $subject, $message, 'Content-type:text/html; charset = UTF-8\nFrom:' . Config::$emailsite);
    }

}
