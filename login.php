<?php
session_start();
if(!empty($_SESSION['user']))
{
    header("Location: index.php");
    die("Redirecting to Home Page");
}

require_once('include/common.php');

if(!empty($_POST))
{
    $db->where ("username", $_POST['username']);
    $result = $db->getOne("users");

    $login_ok = false;

    if($result)
    {
        $check_password = hash('sha256', $_POST['password'] . $result['salt']);
        for($round = 0; $round < 65536; $round++)
        {
            $check_password = hash('sha256', $check_password . $result['salt']);
        }

        if($check_password === $result['password'])
        {
            $login_ok = true;
        }
    }

    if($login_ok)
    {
        unset($result['salt']);
        unset($result['password']);

        $_SESSION['user'] = $result;
        session_write_close();

        header("Location: index.php");
        die("Redirecting to Home Page");
    }
    else
    {
        $message[] =  "Username or Password Incorrect Try Again";
    }
}
$tpl = $m->loadTemplate('login');
if(isset($message)) {
    $data['message'] = $message;
}
echo $tpl->render($data);

