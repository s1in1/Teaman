<?php session_start(); ?>
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
        header("Location: proj.php?id=$project_id"); 
        exit();
      }
    }

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

  <?php include('header.php') ?>
 
  <main class="d-flex flex-nowrap">
    <div class="container d-md-flex flex-md-equal w-100 my-md-3 ps-md-0">
      <div class="d-flex flex-column flex-shrink-0 p-3">

        <h3>Проект: <?php echo $project['name'] ?></h3>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
          <li class="nav-item">
            <a href="#" class="nav-link active" data-filter="all">
              Все задачи
            </a>
          </li>
          <li>
            <a href="#" class="nav-link" data-filter="newtask">
              Новые задачи
            </a>
          </li>
          <li>
            <a href="#" class="nav-link" data-filter="taskpr">
              Задачи в работе
            </a>
          </li>
          <li>
            <a href="#" class="nav-link" data-filter="taskend">
              Закрытые задачи
            </a>
          </li>
        </ul>
      </div>

      <div class="row col-10 my-5" id="product-list">

        <!-- Модальное окно создания задачи -->
        <div class="modal fade" id="createTask" tabindex="-1" aria-labelledby="createTaskLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content rounded-5 p-4 text-start">

              <!-- Заголовок -->
              <div class="modal-header border-0">
                <h3 class="modal-title" id="createTaskModalLabel">Новая задача</h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
              </div>

              <!-- Тело формы -->
              <div class="modal-body">
                <form method="POST">
                  <input type="hidden" name="action" value="create_task">
                  <div class="mb-3">
                    <label for="title" class="form-label">Tема</label>
                    <input type="text" name="title" class="form-control" id="title" placeholder="Введите тему" style="color:#f4f4f4; background-color: #212121;" required>
                  </div>

                  <div class="mb-3">
                    <label for="description" class="form-label">Описание</label>
                    <textarea type="text" name="description" class="form-control" id="description" placeholder="Описание" style="color:#f4f4f4; background-color: #212121;" required></textarea>
                  </div>

                  <!-- Кнопка -->
                  <div class="d-flex justify-content-between align-items-center w-100">
                    <button type="submit" name="createTask" class="btn btn-primary rounded-4 w-35">Создать задачу</button>
                  </div>
                      
                </form>              
              </div>
            </div>
          </div>
        </div>

        <?php while ($task = mysqli_fetch_assoc($task_result)) {
          $task_id = $task['id'];
          $task_comments = [];
          $sql_comments = "SELECT c.comment, u.first_name, u.last_name, c.created_at
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
          <div class="col-md-12 mb-12 my-1 product-card" data-category="<?php echo $task['status']; ?>">
            <div class="card card_team_back">
              <a href="#" data-bs-toggle="modal" data-bs-target="#tasknum<?php echo $task['id']; ?>" class="text-decoration-none text-primary">
                <div class="card-body">
                  <div class="d-flex justify-content-between gap-3">
                    <h5 class="card-team"><?php echo ($task['title']); ?></h5>
                    <span class="text-secondary">
                      <?php if ($task['status'] == 'newtask') echo 'Создан';
                            elseif ($task['status'] == 'taskpr') echo 'В работе';
                            else echo 'Закрыт'; ?>
                    </span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <p class="card-text card-proj-p">Автор: <?php echo ($task['author_name']); ?></p>
                    <p class="card-text card-proj-p">Дата создания: <?php echo ($task['created_at']); ?></p>
                  </div>
                  <div class="d-flex justify-content-between">
                    <p class="card-text card-proj-p">Исполнитель: <?php echo $task['executor_name'] ?: 'Нет'; ?></p>
                    <p class="card-text card-proj-p">Дата исполнения: <?php echo $task['completed_at'] ?: '—'; ?></p>
                  </div>
              </div></a>
            </div>
          </div>

          <!-- Модальное окно задачи -->
          <div class="modal fade" id="tasknum<?php echo $task['id']; ?>" tabindex="-1" aria-labelledby="taskLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
              <div class="modal-content rounded-5 p-4 text-start">

                <!-- Заголовок -->
                <div class="modal-header border-0">
                  <h3 class="modal-title me-3" id="createTaskModalLabel"><?=($task['title']);?></h3>
                  <div class="w-50 text-start">
                    <?php if ($task['status'] == 'newtask') { ?>
                      <button type="submit" name="action" value="take_task" class="btn btn-primary" onclick="takeTask(<?php echo $task['id']; ?>)">Взять в работу</button>
                    <?php } ?>
                    <?php if ($task['status'] == 'taskpr' && $task['executor_id'] == $_SESSION['user_id']) { ?>
                      <button type="submit" name="action" value="complete_task" class="btn btn-primary" onclick="completeTask(<?php echo $task['id']; ?>)">Исполнить</button>
                    <?php } ?>
                  </div>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>

                <!-- Тело  -->
                <div class="modal-body d-flex row justify-content-between">

                  <div class="col-9">
                    <p>Описание: <?php echo ($task['description']); ?></p>
                  </div>
                  <div class="col-3 text-end">
                    <p>Автор: <?php echo ($task['author_name']); ?></p>
                    <p>Дата создания: <?php echo ($task['created_at']); ?></p>
                  </div>

                  <div class="w-100">
                    <form method="POST" class="d-flex gap-1 align-items-center">
                      <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                      <div class="input-group">
                        <textarea name="comment" aria-label="With textarea" class="form-control" placeholder="Введите комментарий" required></textarea>
                        <button type="submit" name="action" value="add_comment" class="btn btn-outline-secondary">Отправить</button>
                      </div>
                    </form>
                  </div>

                    <div class="comments w-100 mt-3">
                      <?php if (!empty($task_comments)): ?>
                        <?php foreach($task_comments as $comment): ?>
                          <div class="comment-item border-bottom pb-2 mb-2">
                            <p class="mb-1"><strong><?= htmlspecialchars($comment['first_name']) ?> <?= htmlspecialchars($comment['last_name']) ?></strong>: <?= htmlspecialchars($comment['comment']) ?></p>
                            <small class="text-muted"><em><?= $comment['created_at'] ?></em></small>
                          </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <p class="text-muted">Комментариев пока нет.</p>
                      <?php endif; ?>
                    </div>

                </div>
              </div>
            </div>
          </div>                
        <?php } ?>

    </div>
  </main>

  <!-- написать переадресацию на задачу, в которой был написан комментарий -->

  <script>
  function takeTask(taskId) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '';

      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'take_task';

      const taskInput = document.createElement('input');
      taskInput.type = 'hidden';
      taskInput.name = 'task_id';
      taskInput.value = taskId;

      form.appendChild(actionInput);
      form.appendChild(taskInput);
      document.body.appendChild(form);
      form.submit();
    }

  function completeTask(taskId) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '';

      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'complete_task';

      const taskInput = document.createElement('input');
      taskInput.type = 'hidden';
      taskInput.name = 'task_id';
      taskInput.value = taskId;

      form.appendChild(actionInput);
      form.appendChild(taskInput);
      document.body.appendChild(form);
      form.submit();
    }

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
