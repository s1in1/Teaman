<?php session_start(); ?>
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

<?php
    $connection = mysqli_connect("localhost", "root", "", "teaman")
        or die("Ошибка подключения: " . mysqli_error($connection));


    if (!isset($_SESSION['user_id'])) {
      header("Location: index.php");
      exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    $sql_proj = "SELECT * FROM projects WHERE id = $project_id";
    $proj_result = mysqli_query($connection, $sql_proj);
    $project = mysqli_fetch_assoc($proj_result);
    if (!$project) die("Проект не найден");

    $sql_check_access = "
      SELECT 1
      FROM project_teams pt
      INNER JOIN team_members tm ON tm.team_id = pt.team_id
      WHERE pt.project_id = $project_id AND tm.user_id = $user_id
      UNION
      SELECT 1
      FROM projects
      WHERE id = $project_id AND owner_id = $user_id
  ";

  $access_result = mysqli_query($connection, $sql_check_access);
  if (mysqli_num_rows($access_result) == 0) die("Нет доступа к проекту");


  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_task') {
        $title = mysqli_real_escape_string($connection, $_POST['title']);
        $description = mysqli_real_escape_string($connection, $_POST['description']);
        $sql_insert = "INSERT INTO tasks (title, description, status, author_id, project_id, created_at)
                       VALUES ('$title', '$description', 'newtask', $user_id, $project_id, NOW())";
        mysqli_query($connection, $sql_insert) or die(mysqli_error($connection));
        header("Location: proj.php?id=$project_id"); exit();
    }

    if ($_POST['action'] === 'take_task') {
        $task_id = (int)$_POST['task_id'];
        $sql_update = "UPDATE tasks SET status = 'taskpr', executor_id = $user_id WHERE id = $task_id";
        mysqli_query($connection, $sql_update) or die(mysqli_error($connection));
        header("Location: proj.php?id=$project_id"); exit();
    }

    if ($_POST['action'] === 'complete_task') {
        $task_id = (int)$_POST['task_id'];
        $check = mysqli_query($connection, "SELECT executor_id FROM tasks WHERE id = $task_id");
        $t = mysqli_fetch_assoc($check);
        if ($t['executor_id'] != $user_id) die("Вы не исполнитель");
          $sql_update = "UPDATE tasks SET status = 'taskend', completed_at = NOW() WHERE id = $task_id";
        mysqli_query($connection, $sql_update) or die(mysqli_error($connection));
        header("Location: proj.php?id=$project_id"); exit();
    }

  if ($_POST['action'] === 'add_comment') {
    $task_id = $_POST['task_id'];
    $user_id = $_SESSION['user_id'];
    $comment = $_POST['comment'];

    $stateCom = $connection->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
    $stateCom->bind_param("iis", $task_id, $user_id, $comment);
    $stateCom->execute();
    $stateCom->close();
    header("Location: proj.php?id=$project_id"); exit();
  }
}
// $task_id_for_comments = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
//
// if ($task_id_for_comments > 0) {
//     $stmt = $connection->prepare(
//         "SELECT c.comment, u.first_name, u.last_name, c.created_at
//         FROM task_comments c
//         JOIN users u ON c.user_id = u.id
//         WHERE c.task_id = ?
//         ORDER BY c.created_at ASC"
//     );
//     $stmt->bind_param("i", $task_id_for_comments);
//     $stmt->execute();
//     $comments = $stmt->get_result();
// } else {
//     $comments = null; // Если task_id нет, $comments = null
// }
// $task_id = $_GET['task_id'];
// $result = $mysqli->prepare(
//   "SELECT c.comment, u.first_name, u.last_name, c.created_at
//   FROM task_comments c
//   JOIN users u ON c.user_id = u.id
//   WHERE c.task_id = ?
//   ORDER BY c.created_at ASC");
// $result->bind_param("i", $task_id);
// $result->execute();
// $comments = $result->get_result();
// }

$task_result = mysqli_query($connection, "
    SELECT t.id, t.title, t.description, t.status, t.created_at, t.completed_at,
        CONCAT(a.first_name, ' ', a.last_name) AS author_name,
        CONCAT(e.first_name, ' ', e.last_name) AS executor_name,
        t.executor_id
    FROM tasks t
    INNER JOIN users a ON t.author_id = a.id
    LEFT JOIN users e ON t.executor_id = e.id
    WHERE t.project_id = $project_id
    ORDER BY t.created_at DESC
");


?>

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
          <div class="flex-shrink-0 dropdown">
          <button class="btn btn-outline-secondary link-body-emphasis text-decoration-none dropdown-toggle show" type="button" data-bs-toggle="dropdown" aria-expanded="true">Профиль</button>
          <ul class="dropdown-menu text-small shadow show" style="position: absolute; inset: 0px 0px auto auto; margin: 0px; transform: translate(0px, 34px);" data-popper-placement="bottom-end">
             <li><a href="profile.php" class="dropdown-item"><?php echo $_SESSION['user_name']; ?></a></li>
             <li><hr class="dropdown-divider"></li> <li><a href="auth.php?logout=1" class="dropdown-item">Выйти</a></li>
           </ul>
         </div>
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

<main class="d-flex flex-nowrap">
  <div class="container d-md-flex flex-md-equal w-100 my-md-3 ps-md-3">
  <div class="d-flex flex-column flex-shrink-0 p-3 text-white">
    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
      <span class="fs-4">Проект: <?php echo $project['name'] ?></span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
      <li class="nav-item">
        <a href="#" class="nav-link text-white" data-filter="all">
          Все задачи
        </a>
      </li>
      <li>
        <a href="#" class="nav-link text-white"  data-filter="newtask">
          Новые задачи
        </a>
      </li>
      <li>
        <a href="#" class="nav-link text-white" data-filter="taskpr">
          Задачи в работе
        </a>
      </li>
      <li>
        <a href="#" class="nav-link text-white" data-filter="taskend">
          Закрытые задачи
        </a>
      </li>
    </ul>
  </div>


<div class="row col-10 my-5" id="product-list">

  <div class="col-md-12 mb-12 my-2 text-end">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
      Создать задачу
    </button>
  </div>

  <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <form class="modal-content" method="POST">
        <input type="hidden" name="action" value="create_task">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Новая задача</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="title" class="form-label">Тема</label>
            <input type="text" class="form-control" id="title" name="title" placeholder="Введите тему" requiredrequired>
          </div>
          <div class="mb-3">
            <label for="description" class="form-label">Описание</label>
            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Введите описание" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
          <button type="submit" class="btn btn-primary">Создать задачу</button>
        </div>
      </form>
    </div>
  </div>

 <?php while ($task = mysqli_fetch_assoc($task_result)) {
   $task_id = $task['id'];
   $task_comments = [];
$sql_comments = "SELECT c.comment,u.first_name, u.last_name, c.created_at
                 FROM task_comments c
                 JOIN users u ON c.user_id = u.id
                 WHERE c.task_id = $task_id
                 ORDER BY c.created_at ASC";

$comments_result = mysqli_query($connection, $sql_comments);
if ($comments_result !== false) {
    while ($comment = mysqli_fetch_assoc($comments_result)) {
        $task_comments[] = $comment;
    }
    mysqli_free_result($comments_result);
}



  ?>
  <div class="col-md-6 mb-6 my-2 product-card" data-category="<?php echo $task['status']; ?>">
    <div class="card">
      <a href="" data-bs-target="#tasknum<?php echo $task['id']; ?>" class="text-decoration-none text-primary">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <h5><?php echo ($task['title']); ?></h5>
            <span class="text-info">Статус:
              <?php     if ($task['status'] == 'newtask') echo 'Создан';
                        elseif ($task['status'] == 'taskpr') echo 'В работе';
                        else echo 'Закрыт'; ?>
            </span>
          </div>
          <div class="d-flex justify-content-between">
            <p class="card-text">Автор: <?php echo ($task['author_name']); ?></p>
            <p class="card-text">Дата создания: <?php echo ($task['created_at']); ?></p>
          </div>
          <div class="d-flex justify-content-between">
            <p class="card-text">Исполнитель: <?php echo $task['executor_name'] ?: 'Нет'; ?></p>
            <p class="card-text">Дата исполнения: <?php echo $task['completed_at'] ?: '—'; ?></p>
          </div>
      </div></a>
    </div>
  </div>

  <div class="modal fade" id="tasknum<?php echo $task['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <form class="modal-content" method="POST">
        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo ($task['title']); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
        </div>
        <div class="modal-body d-flex justify-content-between">
          <div class="col-9">
            <p>Описание: <?php echo ($task['description']); ?></p>
          </div>
          <div class="col-3 text-end">
            <p>Автор: <?php echo ($task['author_name']); ?></p>
            <p>Дата создания: <?php echo ($task['created_at']); ?></p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
          <?php if ($task['status'] == 'newtask') { ?>
                <button type="submit" name="action" value="take_task" class="btn btn-primary">Взять в работу</button>
          <?php } ?>
          <?php if ($task['status'] == 'taskpr' && $task['executor_id'] == $_SESSION['user_id']) { ?>
                 <button type="submit" name="action" value="complete_task" class="btn btn-primary">Исполнить</button>
          <?php } ?>

          <hr>

            <textarea name="comment" required></textarea>

            <button type="submit" name="action" value="add_comment">Добавить комментарий</button>

        <div class="comments">
          <?php if (!empty($task_comments)): ?>
        <?php foreach($task_comments as $comment): ?>
            <p><strong><?= $comment['first_name'] ?> <?= $comment['last_name'] ?></strong>: <?= $comment['comment'] ?> <em><?= $comment['created_at'] ?></em></p>
        <?php endforeach; ?>
        <?php else: ?>
            <p>Комментариев пока нет.</p>
        <?php endif; ?>


        </div>
        </div>
      </form>
    </div>
  </div>
  <?php } ?>

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
