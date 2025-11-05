<header class="py-2 px-2 mb-4 border-bottom border-secondary">
  <div class="container d-flex flex-wrap justify-content-between align-items-center">

    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto">
      <span class="fs-4"><img src="/img/Logo.svg" alt="teaman"></span>
    </a>
       
    <div class="d-flex align-items-center">
        
      <?php
      if (!isset($_SESSION['user_id'])) { 
      ?>

        <a href="#" class="nav-link me-3" data-bs-toggle="modal" data-bs-target="#registerModal">Проекты</a>
        <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#registerModal">Команды</a>

      <?php } else { 

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

        $modal_target = '';
        if (strpos($_SERVER['REQUEST_URI'], '/projects.php') === 0) {
           $modal_target = '#createProject';
        } elseif (strpos($_SERVER['REQUEST_URI'], '/proj.php') === 0) {
          $modal_target = '#createTask';
        }

        if ($modal_target): ?>

          <button type="button" href="#" class="btn btn-primary btn-mg px-4 me-md-2 rounded-4" data-bs-toggle="modal" data-bs-target="<?php echo $modal_target; ?>">Создать</button>
            
        <?php endif; ?>

        <a href="projects.php" class="nav-link me-3">Проекты</a>
        <a href="teams.php" class="nav-link me-3">Команды</a>

        <div class="ms-auto">
          <ul class="nav row text-start flex-column pe-0 me-0">
            <li class="nav-item">
              <a href="#" data-bs-toggle="#profileModal" style="color: #f4f4f4;">
                <?= htmlspecialchars($_SESSION['user_name']) ?>
              </a>
            </li>
            <li class="nav-item">
              <a href="profile.php" style="color: #4d4d4d;">
                <?= htmlspecialchars($_SESSION['user_email']); ?>
              </a>
            </li>
          </ul>  
        </div> 

        <?php } ?>  

    </div> 

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
            <form method="POST" action="auth.php">
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
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-5 p-4 text-start">
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
      </div>
    </div>
  </div>

  <?php 
  
if (isset($_GET['modal']) && $_GET['modal'] == 'success') {
echo'<script>
    document.addEventListener("DOMContentLoaded", function() {
        var modal = new bootstrap.Modal(document.getElementById("loginModal"));
        modal.show();
    });
    </script>';
}
  ?>
</header>