<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-5 p-4 text-start">

      <!-- Заголовок -->
      <div class="modal-header border-0">
        <h3 class="modal-title" id="registrationModalLabel">Профиль</h3>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
      </div>

      <!-- Тело окна -->
      <div class="modal-body">
        <p><strong>Имя:</strong> <?php echo $user['first_name']; ?></p>
        <p><strong>Фамилия:</strong> <?php echo $user['last_name']; ?></p>
        <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
        <p><strong>Команды:</strong> <?php 
  
        ?>
        </p>
        <a href="auth.php?logout=1" class="btn btn-danger w-100 rounded-4 fw-medium">Выйти</a>
      </div>

    </div>
  </div>
</div>
