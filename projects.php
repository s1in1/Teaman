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

<?php
    $conn = mysqli_connect("localhost", "root", "", "teaman")
        or die("Ошибка подключения: " . mysqli_error($conn));

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
        $state = $conn->prepare($sql);
        $state->bind_param("i", $currentUserId);
        $state->execute();
        $projects = $state->get_result()->fetch_all(MYSQLI_ASSOC);


        $userTeams = $conn->query("
          SELECT t.id, t.name
          FROM teams t
          JOIN team_members tm ON t.id = tm.team_id
          WHERE tm.user_id = $currentUserId
        ")->fetch_all(MYSQLI_ASSOC);

      $cat_result = $conn->query("SELECT DISTINCT category FROM projects WHERE category IS NOT NULL AND category != ''");

      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
      $name = trim($_POST['name']);
      $desc = trim($_POST['description']);
      $cat = trim($_POST['category']);
      $user_id = $_SESSION['user_id'];

      if ($name !== '') {
          $state = $conn->prepare("INSERT INTO projects (name, description, category, owner_id) VALUES (?, ?, ?, ?)");
          $state->bind_param("sssi", $name, $desc, $cat, $user_id);
          $state->execute();
          $project_id = $conn->insert_id;

          if (!empty($_POST['teams'])) {
            $teamState = $conn->prepare("INSERT INTO project_teams (project_id, team_id) VALUES (?, ?)");
            foreach ($_POST['teams'] as $teamId) {
              $teamState->bind_param("ii", $project_id, $teamId);
              $teamState->execute();
            }
          }
          $message = "Проект «" . htmlspecialchars($name) . "» создан";
      } else {
        $message = "Введите название проекта";
      }
    }
?>

  <?php include('header.php') ?>

  <main class="d-flex flex-nowrap">

    <div class="container d-md-flex flex-md-equal w-100 my-md-3 ps-md-0">

      <div class="d-flex flex-column flex-shrink-0 p-3">
        <a href="/projects.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none">
          <h3>Проекты</h3>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
          <li class="nav-item">
            <a href="#" class="nav-link" data-filter="all">
              Все проекты
            </a>
          </li>
          <?php while ($cat = mysqli_fetch_assoc($cat_result)) { ?>
            <li>
              <a href="#" class="nav-link" data-filter="<?= htmlspecialchars($cat['category']) ?>">
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

      <div class="modal fade" id="createProject" tabindex="-1">
        <div class="modal-dialog">
          <form method="POST" class="modal-content">
            <div class="modal-header"><h5>Создание проекта</h5></div>
            <div class="modal-body">
              <input type="hidden" name="create_project" value="1">
              <div class="mb-3">
                <label>Название</label>
                <input type="text" name="name" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Описание</label>
                <textarea name="description" class="form-control"></textarea>
              </div>
              <div class="mb-3">
                <label>Категория</label>
                <input type="text" name="category" class="form-control">
              </div>
              <div class="mb-3">
              <label>Добавить команды</label>
              <?php foreach ($userTeams as $team): ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="teams[]" value="<?= $team['id'] ?>" id="team<?= $team['id'] ?>">
                  <label class="form-check-label" for="team<?= $team['id'] ?>">
                    <?= htmlspecialchars($team['name']) ?>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Создать</button>
            </div>
          </form>
        </div>
      </div>

        <?php if (count($projects) > 0): ?>
          <div class="list-group ms-2">
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
