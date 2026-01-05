<?php
session_start();
include("./connection.php");
//протестировать работу всех страниц 
//переделать хедер
//красивые уведомления  
if (isset($_POST['register'])) {
    $first_name = mysqli_real_escape_string($connection, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($connection, $_POST['last_name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = mysqli_query($connection, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['auth_error'] = "Пользователь с таким email уже существует";
        header("Location: index.php");
        exit();
    } 

    $sql = "INSERT INTO users (first_name, last_name, email, password, access_level)
            VALUES ('$first_name', '$last_name', '$email', '$password', 'user')";
    mysqli_query($connection, $sql) or die("Ошибка регистрации: " . mysqli_error($connection));

    $_SESSION['auth_success'] = "Регистрация успешна. Войдите в систему."; 
    header("Location: index.php?modal=success");
    exit();
}

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = $_POST['password'];

    $res = mysqli_query($connection, "SELECT * FROM users WHERE email='$email'");
    $user = mysqli_fetch_assoc($res);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['access_level'] = $user['access_level'];
  
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['auth_error'] = '
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Danger:"><use xlink:href="#exclamation-triangle-fill"/></svg>
            <div>
                Неверный логин или пароль
            </div>
        </div>';
        header('Location: index.php');
        exit();
    }
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}