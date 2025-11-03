<?php session_start(); ?>
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
      <header class="d-flex flex-wrap justify-content-center py-2 mb-4 border-bottom">
          <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-dark text-decoration-none logoIndex">
            <span class="fs-4 text-white">Teaman</span>
          </a>
          <?php if (!isset($_SESSION['user_id'])) { ?>
            <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#loginModal">Проекты</a>
            <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#loginModal">Команды</a>
          <?php } else { ?>
          <a href="projects.php" class="nav-link">Проекты</a>
          <a href="teams.php" class="nav-link">Команды</a>
          <?php } ?>
        <div class="col-md-3 text-end">
          <?php if (!isset($_SESSION['user_id'])) { ?>
            <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Войти</button>
          <?php } else { ?>
            <a href="profile.php" class="text-decoration-none"><?php echo $_SESSION['user_name']; ?></a>
            <a href="auth.php?logout=1" class="btn btn-outline-danger">Выйти</a>
          <?php } ?>
        </div>

        <?php if (isset($_SESSION['auth_error'])) { ?>
          <div class="alert alert-danger"><?php echo $_SESSION['auth_error']; unset($_SESSION['auth_error']); ?></div>
        <?php } ?>

        <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">Вход в аккаунт</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
              </div>
              <div class="modal-body">
                <form class="modal-content" method="POST" action="auth.php">
                  <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Введите email" required>
                  </div>
                  <div class="mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Введите пароль" required>
                  </div>
                  <button type="submit" name="login" class="btn btn-primary w-100">Войти</button>
                </form>
                <hr>
                <div class="text-center">
                  <p>Еще нет аккаунта?</p>
                  <button type="button" class="btn btn-outline-secondary" id="registerBtn" data-bs-target="#registerModal" data-bs-toggle="modal" data-bs-dismiss="modal">Регистрация</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="registerModalLabel">Регистрация</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
              </div>
              <div class="modal-body">
                <form class="modal-content" method="POST" action="auth.php">
                  <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Имя</label>
                    <input type="text" name="first_name" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Фамилия</label>
                    <input type="text" name="last_name" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label for="registerPassword" class="form-label">Пароль</label>
                    <input type="password" name="password" class="form-control" id="registerPassword" placeholder="Введите пароль" required>
                  </div>
                  <button type="submit" name="register" class="btn btn-primary w-100">Зарегистрироваться</button>
                </form>

                <hr>

                <div class="text-center">
                  <p>Уже есть аккаунт?</p>
                  <button type="button" class="btn btn-outline-secondary" data-bs-target="#loginModal" data-bs-toggle="modal" data-bs-dismiss="modal">
                    Войти
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

      </header>

  <div class="container col-xxl-8 px-4 py-5">
  <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
    <div class="col-10 col-sm-8 col-lg-6">
      <!-- <img src="bootstrap-themes.png" class="d-block mx-lg-auto img-fluid" alt="Bootstrap Themes" width="700" height="500" loading="lazy"> -->
    </div>
    <div class="col-lg-6">
      <h1 class="display-5 fw-bold lh-1 mb-3 text-white">Управление проектами и работа c командой в Teaman</h1>
      <p class="lead text-white">Планируйте, отслеживайте и управляйте задачами с помощью современных инструментов.</p>
      <div class="d-grid gap-2 d-md-flex justify-content-md-start">
        <button type="button" class="btn btn-primary btn-mg px-4 me-md-2">Начать сейчас</button>
        <button type="button" class="btn btn-outline-secondary btn-mg px-4">Узнать больше</button>
      </div>
    </div>
  </div>




</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  </body>
</html>
