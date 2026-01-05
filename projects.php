<?php 
  session_start(); 
  include './connection.php';

  if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
  }
  $currentUserId = $_SESSION['user_id'];
  $sql = "
    SELECT DISTINCT p.*
    FROM projects p
    JOIN project_teams pt ON p.id = pt.project_id
    JOIN team_members tm ON tm.team_id = pt.team_id
    WHERE tm.user_id = ?
    ";
  $state = $connection->prepare($sql);
  $state->bind_param("i", $currentUserId);
  $state->execute();
  $projects = $state->get_result()->fetch_all(MYSQLI_ASSOC);
  $userTeams = $connection->query("
    SELECT t.id, t.name
    FROM teams t
    JOIN team_members tm ON t.id = tm.team_id
    WHERE tm.user_id = $currentUserId
    ")->fetch_all(MYSQLI_ASSOC);
  $cat_result = $connection->query("SELECT DISTINCT category FROM projects WHERE category IS NOT NULL AND category != ''");
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $cat = trim($_POST['category']);
    $user_id = $_SESSION['user_id'];
    if ($name !== '') {
      $state = $connection->prepare("INSERT INTO projects (name, description, category, owner_id) VALUES (?, ?, ?, ?)");
      $state->bind_param("sssi", $name, $desc, $cat, $user_id);
      $state->execute();
      $project_id = $connection->insert_id;
      if (!empty($_POST['teams'])) {
        $teamState = $connection->prepare("INSERT INTO project_teams (project_id, team_id) VALUES (?, ?)");
        foreach ($_POST['teams'] as $teamId) {
          $teamState->bind_param("ii", $project_id, $teamId);
          $teamState->execute();
        }
          header("Location: projects.php");
      }
        // else {
        //   // Создаем команду по умолчанию для этого проекта
        //   $code = strtoupper(bin2hex(random_bytes(4)));
        //   $defaultTeamName = "Команда проекта '" . $name . "'";
        //   $teamState = $conn->prepare("INSERT INTO teams (name, access_code) VALUES (?, ?)");
        //   $teamState->bind_param("si", $defaultTeamName, $code);
        //   $teamState->execute();
        //   $defaultTeamId = $conn->insert_id;
          
        //   // Добавляем владельца в команду
        //   $memberState = $conn->prepare("INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, 'owner')");
        //   $memberState->bind_param("ii", $defaultTeamId, $user_id);
        //   $memberState->execute();
          
        //   // Связываем проект с командой по умолчанию
        //   $projectTeamState = $conn->prepare("INSERT INTO project_teams (project_id, team_id) VALUES (?, ?)");
        //   $projectTeamState->bind_param("ii", $project_id, $defaultTeamId);
        //   $projectTeamState->execute();
        //   header("Location: projects.php");
        // }
    } else {
      $message = "Введите название проекта";
    }
  }
?>
<!DOCTYPE html>
<html lang="ru" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&family=Roboto:wght@100..900&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>teaman</title>
  </head>

  <?php include('header.php') ?>

  <main class="d-flex flex-nowrap">

    <div class="container d-md-flex flex-md-equal w-100 my-md-3 ps-md-0">

      <div class="d-flex flex-column flex-shrink-0 p-3 me-3">

        <h3>Проекты</h3>

        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
          <li class="nav-item">
            <a href="#" class="nav-link active ps-2" data-filter="all">
              Все проекты
            </a>
          </li>
          <?php while ($cat = mysqli_fetch_assoc($cat_result)) { ?>
            <li>
              <a href="#" class="nav-link ps-2" data-filter="<?= htmlspecialchars($cat['category']) ?>">
                <?= htmlspecialchars($cat['category']) ?>
              </a>
            </li>
          <?php } ?>
        </ul>
      </div>

      <div class="row col-10 text-end my-5" id="product-list">

        <?php if (isset($message)): ?>
          <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Модальное окно создания проекта -->
        <div class="modal fade" id="createProject" tabindex="-1" aria-labelledby="createProjectLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-5 p-4 text-start">

              <!-- Заголовок -->
              <div class="modal-header border-0">
                <h3 class="modal-title" id="registrationModalLabel">Создание проекта</h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
              </div>

              <!-- Тело формы -->
              <div class="modal-body">
                <form method="POST">
                  <div class="mb-3">
                    <input type="hidden" name="create_project" value="1">
                    <label for="name" class="form-label">Название</label>
                    <input type="text" name="name" class="form-control" id="name" placeholder="Название" style="color:#f4f4f4; background-color: #212121;" required>
                  </div>

                  <div class="mb-3">
                    <label for="description" class="form-label">Описание</label>
                    <textarea type="text" name="description" class="form-control" id="description" placeholder="Описание" style="color:#f4f4f4; background-color: #212121;"></textarea>
                  </div>

                  <div class="mb-3">
                    <label for="category" class="form-label">Категория</label>
                    <input type="text" name="category" class="form-control" id="category" placeholder="Категория" style="color:#f4f4f4; background-color: #212121;" required>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Добавить команды</label>
                    <?php foreach($userTeams as $team): ?>
                      <div class="form-check">                     
                        <input type="checkbox" class="form-check-input" name="teams[]" value="<?= $team['id'] ?>" id="team<?= $team['id'] 
                        //поставить уведомление, что команда обязательна
                        ?>" required>
                        <label class="form-check-label" for="team<?= $team['id'] ?>">
                          <?= htmlspecialchars($team['name']) ?>
                        </label>
                      </div>
                    <?php endforeach; ?>
                  </div>

                  <!-- Кнопка -->
                  <div class="d-flex justify-content-between align-items-center w-100">
                    <button type="submit" name="createProject" class="btn btn-primary rounded-pill w-25">Далее</button>
                  </div>
                      
                </form>              
              </div>
            </div>
          </div>
        </div>

        <?php if (count($projects) > 0): ?>
          <div class="ms-2 row row-cols-1 row-cols-md-4 g-3">
            <?php foreach ($projects as $p): ?>
              <div class="col-md-4 mb-4 my-2 product-card text-start" data-category="<?= ($p['category']) ?>">
                <div class="card card_team_back">
                  <a href="proj.php?id=<?= $p['id'] ?>" class="text-decoration-none text-primary">
                    <div class="card-body">
                      <h5 class="card-team card-title"><?= ($p['name']) ?></h5>
                      <p class="card-text text-muted"><?= ($p['description']) ?></p>
                      <p class="card-text text-muted">Категория: <?= ($p['category']) ?></p>
                    </div>
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p>Нет доступных проектов</p>
        <?php endif; ?>
        </div>
      </div>
    </div>

  </main>

<script>
  const filterButtons = document.querySelectorAll('[data-filter]');
  const productCards = document.querySelectorAll('.product-card');

  filterButtons.forEach(button => {
    button.addEventListener('click', () => {

      filterButtons.forEach(btn => btn.classList.remove('active'));
      button.classList.add('active');

      const filterValue = button.getAttribute('data-filter');

      productCards.forEach(card => {
        const category = card.getAttribute('data-category');
        if (filterValue === 'all' || category === filterValue) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  </body>
</html>
