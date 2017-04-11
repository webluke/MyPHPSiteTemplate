<?php
session_start();
if (empty($_SESSION['user'])) {
    header("Location: index.php");              // Not logged in send them to login
    die("Redirecting to Log In");
}
    require_once('include/common.php');

if ($_SESSION['user']['admin'] == 1) {           // Check to see if the logged in user is an admin
    if (!empty($_GET['uid'])) {                  // Check to see if a user id was sent to be edited
        $db->where('id', $_GET['uid']);
        $data['user'] = $db->getOne('users');
        $tpl = $m->loadTemplate('users_edit');
    } else if ($_GET['action'] == "add") {      // Check to see if and add action was sent
        $tpl = $m->loadTemplate('users_add');
    } else if ($_GET['action'] == "delete") {      // Check to see if a delete action was sent
        if(!empty($_GET['userID'])) {
            $db->where('id',$_GET['userID']);
            $db->delete('users');
        }
        $data['users'] = $db->get('users');
        $data['message'] = "User deleted";
        $tpl = $m->loadTemplate('users_list');
    } else if ($_GET['action'] == "insert") {   // Check to see if add returned with an insert action
        $userOK = checkUserInfo($db);
        if ($userOK == null) {       // Data checked out and now set to be inserted into the dB
            $salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647));

            $password = hash('sha256', $_POST['userPassword'] . $salt);
            for($round = 0; $round < 65536; $round++)
            {
                $password = hash('sha256', $password . $salt);
            }
            if(empty($_POST['userAdmin'])) {
                $userAdmin = 0;
            } else {
                $userAdmin = 1;
            }

            $userData = Array(
                'username' => $_POST['userUserName'],
                'name' => $_POST['userName'],
                'password' => $password,
                'salt' => $salt,
                'email' => $_POST['userEmail'],
                'admin' => $userAdmin
            );
            $id = $db->insert('users', $userData);
            header("Location: users.php");
            if ($id)
                echo 'user was created. Id=' . $id;
            else
                echo 'insert failed: ' . $db->getLastError();
            //die("User Added Redirecting to User List.");
        } else {                                // Send back to the page to correct data with error messages and put data back in form
            $data['post'] = $_POST;
            $data['message'] =  $userOK;
            $tpl = $m->loadTemplate('users_add');
        }
    } else if ($_GET['action'] == "update") {     // Action edit existing users, admin edit other
        $userOK = checkUserInfo($db);
        if ($userOK == null) {       // Data checked out and now set to be inserted into the dB
            $userData = Array();

            $db->where('id', $_POST['uid']);
            $userDBData = $db->getOne('users');

            if(!empty($_POST['userPassword'])) {
                $salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647));
                $password = hash('sha256', $_POST['userPassword'] . $salt);
                for($round = 0; $round < 65536; $round++)
                {
                    $password = hash('sha256', $password . $salt);
                }
                $userData['password'] = $password;
                $userData['salt'] = $salt;
            }

            $userData['name'] =  $_POST['userName'];
            $userData['email'] = $_POST['userEmail'];

            $db->where('id', $_POST['uid']);
            if ($db->update('users', $userData)) {
                echo $db->count . ' records were updated';
                header("Location: users.php");
                die("User Updated Redirecting to User List.");
            } else {
                echo 'update failed: ' . $db->getLastError();
            }
        } else {                                // Send back to the page to correct data with error messages and put data back in form
            $data['post'] = $_POST;
            $data['message'] = $userOK;
            $tpl = $m->loadTemplate('users_edit');
        }
    } else {                                    // Admin loads user page to list of user
        $data['users'] = $db->get('users');
        $tpl = $m->loadTemplate('users_list');
    }
} else {                                        // Normal user page load
    if ($_GET['action'] == "update") {            // User sends edited data back to be checked
        $userOK = checkUserInfo($db);
        if ($userOK == null) {       // Data checked out and now set to be inserted into the dB
            $userData = Array();

            $db->where('id', $_SESSION['user']['id']);
            $userDBData = $db->getOne('users');

            if(!empty($_POST['userPassword'])) {
                $salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647));
                $password = hash('sha256', $_POST['userPassword'] . $salt);
                for($round = 0; $round < 65536; $round++)
                {
                    $password = hash('sha256', $password . $salt);
                }
                $userData['password'] = $password;
                $userData['salt'] = $salt;
            }

            $userData['name'] =  $_POST['userName'];
            $userData['email'] = $_POST['userEmail'];

            $db->where('id', $_SESSION['user']['id']);
            if ($db->update('users', $userData)) {
                echo $db->count . ' records were updated';
                header("Location: users.php");
                die("User Updated Redirecting to User List.");
            } else {
                echo 'update failed: ' . $db->getLastError();
            }
        } else {                                // Send back to the page to correct data with error messages and put data back in form
            $data['post'] = $_POST;
            $data['message'] = $userOK;
            $tpl = $m->loadTemplate('users_edit');
        }
    } else {                                    // Load edit form filled with non-admin logged in users data
        $db->where('id', $_SESSION['user']['id']);
        $data['user'] = $db->getOne('users');
        $tpl = $m->loadTemplate('users_edit');
    }
}
//$data['message'] = $message;                    // Error messages added to $data
echo $tpl->render($data);                       // Render page with the template based on states and data

// Data validation stuff needs to be put into a function or something so it can be used more than once
function checkUserInfo($db)
{
    $userOk = true;
    $errors = "";

    if (empty($_POST['userUserName'])) {
        $userOk = false;
        $errors =+ "Please enter a username<br />";
    }

    if (empty($_POST['userPassword'])) {
        $userOk = false;
        $errors =+ "Please enter a password<br />";
    }

    if (!filter_var($_POST['userEmail'], FILTER_VALIDATE_EMAIL)) {
        $userOk = false;
        $errors =+ "Invalid E-Mail Address<br />";
    }

    if ($db->where('username', $_POST['userUserName'])->get('users') > 0) {
        $userOk = false;
        $errors =+ "This username is already in use<br />";
    }

    if ($db->where('email', $_POST['userEmail'])->get('users') > 0) {
        $userOk = false;
        $errors =+ "This email address is already registered<br />";
    }

    if ($userOk) {
        return null;
    } else {
        return $errors;
    }
}

function checkUserInfoExisting()
{
    $userOk = true;
    $errors = "";

    if (empty($_POST['userUserName'])) {
        $userOk = false;
        $errors =+ "Please enter a username<br />";
    }

    if (empty($_POST['userPassword'])) {
        $userOk = false;
        $errors =+ "Please enter a password<br />";
    }

    if (!filter_var($_POST['userEmail'], FILTER_VALIDATE_EMAIL)) {
        $userOk = false;
        $errors =+ "Invalid E-Mail Address<br />";
    }

    if ($userOk) {
        return null;
    } else {
        return $errors;
    }
}