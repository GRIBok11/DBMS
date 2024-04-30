<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ConferenceController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return new Response(<<<EOF
<html>
<head>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('registerButton').addEventListener('click', function() {
                window.location.href = '/blog/list';
            });
        });
    </script>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('logButton').addEventListener('click', function() {
                window.location.href = '/blog/log';
            });
        });
    </script>
</head>
<body>                    
    <input type="submit" id="registerButton" value="Зарегистрироваться">
    <input type="submit" id="logButton" value="Войти">
</body>
</html>
EOF
        );
    }

    #[Route('/blog/list', name: 'reg', priority: 2)]
    public function list(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('Name');
            $email = $request->request->get('Email');
            $password = $request->request->get('Password');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
            {
                if($email=="")
                {
                return $this->redirectToRoute('log');
                }
                else{
                    echo "Адрес указан неверно.";
                }
              
            } else {
                $dsn = 'mysql:dbname=app;host=127.0.0.1';
                $user = 'app';
                $password = 'secret';

                try {
                    $dbn = new \PDO($dsn, $user, $password);
                    $dbn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                    $checkEmail = $dbn->prepare("SELECT * FROM users WHERE email = ?");
                    $checkEmail->execute([$email]);

                    if ($checkEmail->rowCount() > 0) {
                        echo "Email уже занят, пожалуйста, используйте другой email.";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
                        $stmt = $dbn->prepare($sql);
                        $stmt->execute([$name, $email, $hashed_password]);
                        echo "Успешно добавлен новый пользователь.";
                    }
                } catch (\PDOException $e) {
                    echo "Ошибка при подключении к базе данных: " . $e->getMessage();
                }
            }
        }
      
        return new Response(<<<EOF
<html>
<head>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('registerButton').addEventListener('click', function() {
                window.location.href = '/blog/list';
            });
        });
    </script>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('logButton').addEventListener('click', function() {
                window.location.href = '/blog/log';
            });
        });
    </script>
</head>
<body>                    
<form method="post">
<p>Введите имя: <input type="text" name="Name" autocomplete="off"></p>
<p>Введите email: <input type="text" name="Email" autocomplete="off"></p>
<p>Введите пароль: <input type="password" name="Password" autocomplete="off"></p>
<input type="submit" id="registerButton" value="Зарегистрироваться">
    <input type="submit" id="logButton" value="Войти">
    <p><button onclick="window.location.href='{{ path('homepage') }}';">На главную</button></p>
</form>
</body>
</html>
EOF
        );
    }
    #[Route('/blog/log', name: 'log', priority: 3)]
    public function nelist(Request $request,SessionInterface $session): Response
    {
        if ($request->isMethod('POST')) {          
            $email = $request->request->get('Email');
            $password = $request->request->get('Password');
        
        $dsn = 'mysql:dbname=app;host=127.0.0.1';
                $user = 'app';
                $password = 'secret';        
        try {
            $dbn = new \PDO($dsn, $user, $password);
            $dbn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                           
            $stmt = $dbn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        
            if ($user && password_verify($password, $user['password'])) {
                $session->set('user', ['id' => $user['id'], 'name' => $user['name']]);                   
                    return $this->redirectToRoute('com');
            }  
                 else if($user=="")
                 {
                    return $this->redirectToRoute('reg');
                 }
                 else{
                        echo "Неверный логин или пароль.";
                     }                      
            }
         catch (\PDOException $e) {
            echo "Ошибка подключения к базе данных: " . $e->getMessage();
        }
    }


        return new Response(<<<EOF
        <html>
        <head>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('registerButton').addEventListener('click', function() {
                window.location.href = '/blog/list';
            });
        });
    </script>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('logButton').addEventListener('click', function() {
                window.location.href = '/blog/log';
            });
        });
    </script>
</head>
        <body>                    
        <form method="post">     
        <p>Введите логин(email): <input type="text" name="Email" autocomplete="off"></p>
        <p>Введите пароль: <input type="password" name="Password" autocomplete="off"></p>
        <input type="submit" id="registerButton" value="Зарегистрироваться">
        <input type="submit" id="logButton" value="Войти">
        <p><button onclick="window.location.href='{{ path('homepage') }}';">На главную</button></p>
        </form>
        </body>
        </html>
EOF
                );
    }
    #[Route('/blog/com', name: 'com', priority: 5)]
    public function welcome(SessionInterface $session): Response
    {
        $user = $session->get('user');
        if ($user) {
            return new Response("Добро пожаловать, {$user['name']}! <a href='/logout'>Выйти</a>");
        } else {
            return $this->redirectToRoute('log');
        }
    }

    #[Route('/logout', name: 'logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->clear();
        return $this->redirectToRoute('homepage');
    }
}




