<?php 
  session_start();
  if (!isset($_SESSION['user_id'])) {
      header("Location: index.php");
      exit();
  }
  $connection = mysqli_connect("localhost", "root", "", "teaman");
  $user_id = (int)$_SESSION['user_id'];

  $res = mysqli_query($connection, "
      SELECT u.*, t.name AS team_name
      FROM users u
      LEFT JOIN team_members tm ON u.id = tm.user_id
      LEFT JOIN teams t ON tm.team_id = t.id
      WHERE u.id = $user_id
  ");
  $user = mysqli_fetch_assoc($res);
?>

<!DOCTYPE html>
<html lang="ru" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>teaman</title>
  </head>
  <body>

    <?php include('header.php') ?>
    
    <div class="container">

      <div class="container col-xxl-8 px-4 py-5">

        <div class="row flex-lg-row-reverse align-items-center justify-content-center g-5 py-5">

          <div class="col-lg-6">
            <h3 class="mb-3">Профиль</h3>
            <p><strong>Имя:</strong> <?php echo $user['first_name']; ?></p>
            <p><strong>Фамилия:</strong> <?php echo $user['last_name']; ?></p>
            <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
            <p><strong>Уровень доступа:</strong> <?php echo $user['access_level']; ?></p>
            <p><strong>Команда:</strong> <?php echo $user['team_name'] ?: 'Не назначена'; ?></p>
            <hr>
            <a href="auth.php?logout=1" class="btn btn-danger w-100">Выйти</a>
          </div>

        </div>

      </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  </body>
</html>
