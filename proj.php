<?php 
  session_start(); 
  include "./connection.php";

  require_once 'vendor/autoload.php';

  function purify_html($html) {
    $config = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($html);
  }

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
      header("Location: proj.php?id=$project_id"); 
      exit();
    }
    if ($_POST['action'] === 'take_task') {
      $task_id = (int)$_POST['task_id'];
      $sql_update = "UPDATE tasks SET status = 'taskpr', executor_id = $user_id WHERE id = $task_id";
      mysqli_query($connection, $sql_update) or die(mysqli_error($connection));
      header("Location: proj.php?id=$project_id"); 
      exit();
    }
    if ($_POST['action'] === 'complete_task') {
      $task_id = (int)$_POST['task_id'];
      $check = mysqli_query($connection, "SELECT executor_id FROM tasks WHERE id = $task_id");
      $t = mysqli_fetch_assoc($check);
      if ($t['executor_id'] != $user_id) die("Вы не исполнитель");
      $sql_update = "UPDATE tasks SET status = 'taskend', completed_at = NOW() WHERE id = $task_id";
      mysqli_query($connection, $sql_update) or die(mysqli_error($connection));
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
<!DOCTYPE html>
<html lang="ru" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&family=Roboto:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>teaman</title>
  </head>
  <body>

  <?php include('header.php') ?>
 
  <main class="d-flex flex-nowrap">
    <div class="container-fluid d-md-flex flex-md-equal w-100 my-md-3 ps-md-0">
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
                    <?php
                      if (isset($task['completed_at'])) {
                        echo '<p class="card-text card-proj-p">Дата исполнения: '. $task['completed_at']. '</p>';
                      }
                    ?>
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
                      <button type="submit" name="action" value="take_task" class="btn btn-primary rounded-4" onclick="takeTask(<?php echo $task['id']; ?>)">Взять в работу</button>
                    <?php } ?>
                    <?php if ($task['status'] == 'taskpr' && $task['executor_id'] == $_SESSION['user_id']) { ?>
                      <button type="submit" name="action" value="complete_task" class="btn btn-primary rounded-4" onclick="completeTask(<?php echo $task['id']; ?>)">Исполнить</button>
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
                    <form method="POST" class="comment-form d-flex flex-column gap-2" data-task-id="<?php echo $task['id']; ?>">
                      <!-- Скрытое поле для HTML -->
                      <input type="hidden" name="comment" class="comment-hidden" id="commentHidden<?php echo $task['id']; ?>">
                      
                      <!-- Контейнер для Quill (создастся динамически) -->
                      <div class="quill-container" style="height: 100px;"></div>
                      
                      <!-- Старый textarea (оставляем для совместимости) -->
                      <textarea name="comment" class="form-control comment-text" placeholder="Введите комментарий" style="display: none;"></textarea>
                      
                      <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                          Отправить комментарий
                        </button>
                      </div>
                    </form>
                  </div>

                  <div class="comments w-100 mt-3">
                    <?php if (!empty($task_comments)): ?>
                      <?php foreach($task_comments as $comment): ?>
                        <div class="comment-item border-bottom pb-2 mb-1">
                          <p class="mb-1"><strong><?= htmlspecialchars($comment['first_name']) ?> <?= htmlspecialchars($comment['last_name']) ?></strong>:</p>
                          <div class="comment-content"><?= $comment['comment'] ?></div>
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>  
  <script>
  // Проверка загрузки Quill
  if (typeof Quill === 'undefined') {
    console.error('Quill.js не загружен!');
    
    // Fallback: показываем обычные textarea
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.comment-text').forEach(textarea => {
        textarea.style.display = 'block';
      });
    });
  }  

  // Глобальное хранилище для редакторов
  const commentEditors = {};

  // Функция инициализации редактора для конкретной задачи
  function initCommentEditorForTask(taskId) {
    const editorId = `commentEditor${taskId}`;
    const form = document.querySelector(`.comment-form[data-task-id="${taskId}"]`);
    
    if (!form) return;
    
    // Проверяем, не инициализирован ли уже редактор
    if (commentEditors[editorId]) {
      return commentEditors[editorId];
    }
    
    // Находим или создаем контейнер для редактора
    let editorContainer = document.getElementById(editorId);
    let textarea = form.querySelector('textarea.comment-text');
    
    if (!editorContainer && textarea) {
      // Создаем контейнер для Quill
      editorContainer = document.createElement('div');
      editorContainer.id = editorId;
      editorContainer.style.height = '100px';
      editorContainer.style.minHeight = '100px';
      editorContainer.style.marginBottom = '10px';
      
      // Создаем скрытое поле
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'comment';
      hiddenInput.id = `commentHidden${taskId}`;
      hiddenInput.className = 'comment-hidden';
      
      // Заменяем textarea
      textarea.parentNode.insertBefore(editorContainer, textarea);
      textarea.parentNode.insertBefore(hiddenInput, editorContainer);
      textarea.style.display = 'none';
    }
    
    // Инициализируем Quill
    if (editorContainer && !editorContainer.querySelector('.ql-toolbar')) {
      try {
        const quill = new Quill(`#${editorId}`, {
          theme: 'snow',
          modules: {
            toolbar: [
              ['bold', 'italic', 'underline'],
              [{ 'list': 'ordered'}, { 'list': 'bullet' }],
              ['link'],
              ['clean']
            ]
          },
          placeholder: 'Введите комментарий...'
        });
        
        commentEditors[editorId] = quill;
        return quill;
      } catch (error) {
        console.error('Ошибка инициализации Quill:', error);
        return null;
      }
    }
    
    return commentEditors[editorId] || null;
  }

  // Функция для очистки редактора
  function clearCommentEditor(taskId) {
    const editorId = `commentEditor${taskId}`;
    const editor = commentEditors[editorId];
    if (editor) {
      editor.root.innerHTML = '';
    }
  }

  // Обработчик открытия модального окна
  document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем обработчики для модальных окон
    document.querySelectorAll('[id^="tasknum"]').forEach(modal => {
      const modalId = modal.id.replace('tasknum', '');
      
      modal.addEventListener('shown.bs.modal', function() {
        // Ждем пока модальное окно полностью откроется
        setTimeout(() => {
          initCommentEditorForTask(modalId);
        }, 100);
      });
      
      // Очищаем редактор при закрытии модального окна
      modal.addEventListener('hidden.bs.modal', function() {
        clearCommentEditor(modalId);
      });
    });
    
    // Обработчик отправки формы комментария
    document.querySelectorAll('.comment-form').forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const taskId = this.dataset.taskId;
        const editor = initCommentEditorForTask(taskId);
        
        if (!editor) {
          alert('Редактор не загружен. Попробуйте снова.');
          return;
        }
        
        const commentHtml = editor.root.innerHTML;
        const cleanText = commentHtml.trim();
        
        if (!cleanText) {
          alert('Комментарий не может быть пустым');
          return;
        }
        
        // Заполняем скрытое поле
        const hiddenField = document.getElementById(`commentHidden${taskId}`);
        if (hiddenField) {
          hiddenField.value = commentHtml;
        }
        
        const commentsContainer = this.closest('.modal-body').querySelector('.comments');
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Показываем индикатор загрузки
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        submitBtn.disabled = true;
        
        // Отправляем AJAX запрос
        fetch('add_comment.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            task_id: taskId,
            comment: commentHtml,
            action: 'add_comment'
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            editor.root.innerHTML = '';
            updateComments(commentsContainer, taskId);
          } else {
            alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Ошибка при отправке комментария');
        })
        .finally(() => {
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        });
      });
    });
  });

  // Функция обновления комментариев
  function updateComments(container, taskId) {
    const originalContent = container.innerHTML;
    container.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm"></div></div>';
    
    fetch(`get_comments.php?task_id=${taskId}`)
      .then(response => response.text())
      .then(html => {
        container.innerHTML = html;
      })
      .catch(error => {
        console.error('Error:', error);
        container.innerHTML = originalContent;
      });
  }

  // Функции для работы с задачами (оставляем как есть)
  function takeTask(taskId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    form.innerHTML = `
      <input type="hidden" name="action" value="take_task">
      <input type="hidden" name="task_id" value="${taskId}">
    `;
    document.body.appendChild(form);
    form.submit();
  }

  function completeTask(taskId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    form.innerHTML = `
      <input type="hidden" name="action" value="complete_task">
      <input type="hidden" name="task_id" value="${taskId}">
    `;
    document.body.appendChild(form);
    form.submit();
  }

  // Код фильтрации задач
  const filterButtons = document.querySelectorAll('[data-filter]');
  const productCards = document.querySelectorAll('.product-card');

  filterButtons.forEach(button => {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      
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
  </body>
</html>
