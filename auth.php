<?php
session_start();
include("./connection.php");

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

    header("Location: index.php?modal=success");
    exit();
}

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = $_POST['password'];

    $res = mysqli_query($connection, "SELECT * FROM users WHERE email='$email'");

    if (mysqli_num_rows($res) > 0) {
        $user = mysqli_fetch_assoc($res);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['access_level'] = $user['access_level'];
        
            header("Location: teams.php");
            exit();
        } else {
            $_SESSION['auth_error'] = 'Неверный логин или пароль';
            header('Location: index.php');
            exit();
        }
    } else {
        $_SESSION['auth_error'] = "Пользователя с таким email не существует";
        header("Location: index.php");
        exit();
    }

}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}