<?php 
include('connection.php'); 
if (!isset($_SESSION['user_id'])) { 
  if (isset($_SESSION['auth_error'])) { 
    $error_js = htmlspecialchars($_SESSION['auth_error'], ENT_QUOTES);
    echo "<script>alert('$error_js');</script>";
    unset($_SESSION['auth_error']); 
  }
} else {
    $user_id = (int)$_SESSION['user_id'];
    $res = mysqli_query($connection, "
      SELECT u.*, t.name AS team_name
      FROM users u
      LEFT JOIN team_members tm ON u.id = tm.user_id
      LEFT JOIN teams t ON tm.team_id = t.id
      WHERE u.id = $user_id
    ");
    $user = mysqli_fetch_assoc($res);
    $modal_target = '';
    if (strpos($_SERVER['REQUEST_URI'], '/projects.php') === 0) {
      $modal_target = '#createProject';
    } elseif (strpos($_SERVER['REQUEST_URI'], '/proj.php') === 0) {
      $modal_target = '#createTask';
    }
  }
?>
<header class="border-bottom border-secondary">
  <nav class="navbar navbar-expand-lg" aria-label="navbar"> 
    <div class="container"> 
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation"> 
        <span class="navbar-toggler-icon"></span> 
      </button> 
      <div class="collapse navbar-collapse d-lg-flex" id="navbar"> 
        <a class="navbar-brand col-3 me-0" href="#"><img src="/img/logo.svg" alt="Тимэн"></a> 
        <ul class="navbar-nav col-6 justify-content-lg-center"> 
          <li class="nav-item"> 
            <a class="nav-link <?php if ($_SERVER['REQUEST_URI'] === '/') echo 'active'; ?> rounded-4" aria-current="page" href="/">Главная</a> 
          </li> 
          <li class="nav-item"> 
            <a class="nav-link <?php if ($_SERVER['REQUEST_URI'] === '/projects.php') echo 'active'; ?> rounded-4" href="projects.php">Проекты</a> 
          </li> 
          <li class="nav-item"> 
            <a class="nav-link <?php if ($_SERVER['REQUEST_URI'] === '/teams.php') echo 'active'; ?> rounded-4" href="teams.php">Команды</a> 
          </li>
          <?php if ($modal_target): ?>
          <li class="nav-item"> 
            <button type="button" href="#" class="btn btn-primary rounded-4" data-bs-toggle="modal" data-bs-target="<?php echo $modal_target; ?>">Создать</button> 
          </li>
          <?php endif; ?>
        </ul>
        <div class="d-lg-flex col-3 justify-content-lg-end"> 
          <ul class="nav row text-start flex-column pe-0 me-0">
            <li class="nav-item">
              <a href="#" data-bs-toggle="modal" data-bs-target="#profileModal" style="color: #f4f4f4;">
                <?= htmlspecialchars($_SESSION['user_name']) ?>
              </a>
            </li>
            <li class="nav-item">
              <a href="profile.php" data-bs-toggle="modal" data-bs-target="#profileModal" style="color: #c1c1c1;">
                <?= htmlspecialchars($_SESSION['user_email']); ?>
              </a>
            </li>
          </ul>  
        </div> 
      </div> 
    </div> 
  </nav>

  <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center">

    <!-- Модальное окно регистрации-->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-5 p-4 text-start">

          <!-- Заголовок -->
          <div class="modal-header border-0">
            <h3 class="modal-title" id="registrationModalLabel">Регистрация</h3>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
          </div>

          <!-- Тело формы -->
          <div class="modal-body">
            <form method="POST" action="auth.php" autocomplete="on">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="first_name" class="form-label">Имя</label>
                  <input type="text" name="first_name" class="form-control" id="first_name" placeholder="Имя" style="color:#f4f4f4; background-color: #212121;" required>
                </div>

                <div class="col-md-6">
                  <label for="last_name" class="form-label">Фамилия</label>
                  <input type="text" name="last_name" class="form-control" id="last_name" placeholder="Фамилия" style="color:#f4f4f4; background-color: #212121;" required>
                </div>
              </div>

              <div class="mb-3">
                <label for="email" class="form-label">Электронная почта</label>
                <input type="email" name="email" class="form-control" id="email" placeholder="Электронная почта" style="color:#f4f4f4; background-color: #212121;" required>
              </div>

              <div class="mb-3 position-relative">
                <label for="registerPassword" class="form-label">Пароль</label>
                <input type="password" name="password" class="form-control" id="registerPassword" placeholder="Пароль" style="color:#f4f4f4; background-color: #212121;" required>
              </div>

              <!-- Кнопка -->
              <div class="d-flex justify-content-between align-items-center w-100">
                <button type="submit" name="register" class="btn btn-primary rounded-pill w-25">Далее</button>
                <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Уже есть аккаунт?</a>
              </div>
                  
            </form>              
          </div>
        </div>
      </div>
    </div>

    <!-- Модальное окно авторизации -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-5 p-4 text-start">

          <!-- Заголовок -->
          <div class="modal-header border-0">
            <h3 class="modal-title" id="registrationModalLabel">Вход</h3>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
          </div>

          <!-- Тело формы -->
          <div class="modal-body">
            <form method="POST" action="auth.php">

              <div class="mb-3">
                <label for="email"  class="form-label">Электронная почта</label>
                <input type="email" name="email" id="email" class="form-control" id="email" placeholder="Электронная почта" style="color:#f4f4f4; background-color: #212121;" required>
              </div>

              <div class="mb-3 position-relative">
                <label for="registerPassword" class="form-label">Пароль</label>
                <input type="password" id="password" name="password" class="form-control" id="registerPassword" placeholder="Пароль" style="color:#f4f4f4; background-color: #212121;" required>
              </div>

              <!-- Кнопка -->
              <button type="submit" name="login" class="btn btn-primary rounded-pill w-25">Войти</button>
            </form>
          </div>

        </div>
      </div>
    </div>

    <!-- Модальное окно профиля -->
    <?php
      include('profile.php')
    ?>

  </div>

  <?php 
    if (isset($_GET['modal']) && $_GET['modal'] == 'success') {
    echo '<script>
      document.addEventListener("DOMContentLoaded", function() {
        var modal = new bootstrap.Modal(document.getElementById("loginModal"));
        modal.show();
      });
    </script>';
    }
  ?>
</header>