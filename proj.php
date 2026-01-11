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
    <title>Тимэн</title>
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
                    <p class="card-text card-proj-p">Дата создания: <?php echo (date('d.m.Y H:i', strtotime($task['created_at']))); ?></p>
                  </div>
                  <div class="d-flex justify-content-between">
                    <p class="card-text card-proj-p">Исполнитель: <?php echo $task['executor_name'] ?: 'Нет'; ?></p>
                    <?php
                      if (isset($task['completed_at'])) {
                        echo '<p class="card-text card-proj-p">Дата исполнения: '. date('d.m.Y H:i', strtotime($task['completed_at'])) . '</p>';
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
                          <h3 class="modal-title me-3" id="taskModalLabel<?php echo $task['id']; ?>"><?= htmlspecialchars($task['title']); ?></h3>
                          <div class="w-50 text-start">
                              <?php if ($task['status'] == 'newtask') { ?>
                                  <button type="button" class="btn btn-primary rounded-4" onclick="takeTask(<?php echo $task['id']; ?>)">Взять в работу</button>
                              <?php } ?>
                              <?php if ($task['status'] == 'taskpr' && $task['executor_id'] == $_SESSION['user_id']) { ?>
                                  <button type="button" class="btn btn-primary rounded-4" onclick="completeTask(<?php echo $task['id']; ?>)">Исполнить</button>
                              <?php } ?>
                          </div>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                      </div>

                      <!-- Тело модального окна -->
                      <div class="modal-body">
                        <!-- Информация о задаче -->
                        <div class="row mb-4">
                            <div class="col-md-8 mb-3">
                                <h5>Описание:</h5>
                                <div class="task-description h-100 p-3 border border-secondary rounded-2">
                                <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                                </div>
                            </div>
                        <div class="col-md-4 d-flex flex-column justify-content-between">
                            <div>
                              <strong>Автор:</strong>
                              <?= htmlspecialchars($task['author_name']); ?>
                            </div>
                            <div >
                              <strong>Дата создания:</strong><br>
                              <?= date('d.m.Y H:i', strtotime($task['created_at'])); ?>
                            </div>
                            <div >
                              <strong>Исполнитель:</strong><br>
                              <?= !empty($task['executor_name']) ? htmlspecialchars($task['executor_name']) : 'Не назначен'; ?>
                            </div>
                            <div >
                              <strong>Статус:</strong><br>
                              <?php
                              $status_text = '';
                              switch($task['status']) {
                                  case 'newtask': $status_text = 'Новая'; break;
                                  case 'taskpr': $status_text = 'В работе'; break;
                                  case 'taskend': $status_text = 'Завершена'; break;
                                  default: $status_text = $task['status'];
                              }
                              echo $status_text;
                              ?>
                            </div>
                            <?php if (!empty($task['completed_at'])): ?>
                            <div>
                              <strong>Дата завершения:</strong><br>
                              <?= date('d.m.Y H:i', strtotime($task['completed_at'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                        <!-- Форма комментария -->
                        <div class="mb-4">
                            <h5>Добавить комментарий</h5>
                            <form method="POST" class="comment-form" data-task-id="<?php echo $task['id']; ?>">

                                <input type="hidden" name="comment" id="commentHidden<?php echo $task['id']; ?>" class="comment-hidden">

                                <!-- Контейнер для Quill редактора -->
                                <div id="commentEditor<?php echo $task['id']; ?>" style="height: 150px; margin-bottom: 10px;"></div>

                                <textarea name="comment" class="form-control comment-text" placeholder="Введите комментарий" style="display: none;"></textarea>

                                <div class="d-flex justify-content-end mt-2">
                                    <button type="submit" class="btn btn-primary rounded-4">Отправить комментарий</button>
                                </div>
                            </form>
                        </div>
                        <!-- Комментарии -->
                        <div class="comments-section">
                            <h5>Комментарии</h5>
                            <div class="comments" id="commentsContainer<?php echo $task['id']; ?>">
                                <?php if (!empty($task_comments)): ?>
                                    <?php foreach($task_comments as $comment): ?>
                                        <div class="comment-item border-start border-secondary ps-3 py-2 mb-3">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <strong class="comment-author">
                                                      <?= htmlspecialchars($comment['first_name']) ?> <?= htmlspecialchars($comment['last_name']) ?>
                                                  </strong>
                                                  <small class="text-muted">
                                                      <?= date('d.m.Y H:i', strtotime($comment['created_at'])); ?>
                                                  </small>
                                              </div>
                                              <div class="comment-content">
                                                  <?= $comment['comment'] ?>
                                              </div>
                                          </div>
                                      <?php endforeach; ?>
                                  <?php else: ?>
                                      <div class="text-center py-4 text-muted">
                                          <i class="bi bi-chat-left" style="font-size: 2rem;"></i>
                                          <p class="mt-2">Комментариев пока нет</p>
                                      </div>
                                  <?php endif; ?>
                              </div>
                          </div>

                      </div>
                  </div>
              </div>
          </div>
        <?php } ?>

    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
  <script>
  // Глобальное хранилище для редакторов
  const commentEditors = {};

  // Функция инициализации редактора
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
          editorContainer.style.height = '150px';
          editorContainer.style.minHeight = '150px';
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
                          ['link', 'image'],
                          ['clean']
                      ]
                  },
                  placeholder: 'Введите комментарий...'
              });

              // Кастомный обработчик для загрузки изображений
              const toolbar = quill.getModule('toolbar');
              if (toolbar) {
                  // Переопределяем стандартный обработчик изображений
                  toolbar.addHandler('image', function() {
                      selectImage(taskId, quill);
                  });
              }

              commentEditors[editorId] = quill;
              console.log('Quill редактор инициализирован для задачи', taskId);
              return quill;

          } catch (error) {
              console.error('Ошибка инициализации Quill:', error);
              return null;
          }
      }

      return commentEditors[editorId] || null;
  }

  // Функция выбора и загрузки изображения
  function selectImage(taskId, quill) {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.style.display = 'none';

      input.addEventListener('change', async (e) => {
          const file = e.target.files[0];
          if (!file) return;

          // Проверка типа файла
          const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
          if (!allowedTypes.includes(file.type)) {
              alert('Разрешены только изображения (JPEG, PNG, GIF, WebP)');
              input.remove();
              return;
          }

          // Проверка размера файла (5MB)
          if (file.size > 5 * 1024 * 1024) {
              alert('Изображение слишком большое. Максимальный размер: 5MB');
              input.remove();
              return;
          }

          // Показываем простой индикатор
          const button = quill.container.previousSibling.querySelector('.ql-image');
          const originalHtml = button.innerHTML;
          button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
          button.disabled = true;

          try {
              const formData = new FormData();
              formData.append('file', file);
              formData.append('task_id', taskId);

              const response = await fetch('upload_handler.php', {
                  method: 'POST',
                  body: formData
              });

              if (!response.ok) {
                  throw new Error('Ошибка сервера: ' + response.status);
              }

              const result = await response.json();

              if (result.success) {
                  // Вставляем изображение в редактор
                  const range = quill.getSelection();
                  quill.insertEmbed(range.index, 'image', result.url);
              } else {
                  throw new Error(result.error || 'Ошибка загрузки');
              }
          } catch (error) {
              console.error('Ошибка загрузки изображения:', error);
              alert('Ошибка загрузки: ' + error.message);
          } finally {
              // Восстанавливаем кнопку
              button.innerHTML = originalHtml;
              button.disabled = false;
              input.remove();
          }
      });

      document.body.appendChild(input);
      input.click();
  }

  // Инициализация при загрузке DOM
  document.addEventListener('DOMContentLoaded', function() {
      // Инициализируем редакторы при открытии модальных окон
      document.querySelectorAll('[id^="tasknum"]').forEach(modal => {
          const modalId = modal.id.replace('tasknum', '');

          modal.addEventListener('shown.bs.modal', function() {
              setTimeout(() => {
                  initCommentEditorForTask(modalId);
              }, 100);
          });

          // Очищаем редактор при закрытии модального окна
          modal.addEventListener('hidden.bs.modal', function() {
              const editorId = `commentEditor${modalId}`;
              const editor = commentEditors[editorId];
              if (editor) {
                  editor.root.innerHTML = '';
              }
          });
      });

      // Обработчик отправки формы комментария
      document.querySelectorAll('.comment-form').forEach(form => {
          form.addEventListener('submit', async function(e) {
              e.preventDefault();

              const taskId = this.dataset.taskId;
              const editorId = `commentEditor${taskId}`;
              const editor = commentEditors[editorId];

              if (!editor) {
                  alert('Редактор комментариев не загружен. Пожалуйста, закройте и откройте окно задачи снова.');
                  return;
              }

              const commentHtml = editor.root.innerHTML;
              const cleanText = commentHtml.replace(/<[^>]*>/g, '').trim();

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

              try {
                  const formData = new FormData();
                  formData.append('task_id', taskId);
                  formData.append('comment', commentHtml);
                  formData.append('action', 'add_comment');

                  const response = await fetch('add_comment.php', {
                      method: 'POST',
                      body: formData
                  });

                  const responseText = await response.text();
                  let data;
                  try {
                      data = JSON.parse(responseText);
                  } catch (parseError) {
                      console.error('Ошибка парсинга JSON:', responseText);
                      throw new Error('Сервер вернул некорректный ответ');
                  }

                  if (data.success) {
                      // Очищаем редактор
                      editor.root.innerHTML = '';

                      // Обновляем список комментариев
                      await updateComments(commentsContainer, taskId);
                  } else {
                      throw new Error(data.message || 'Ошибка при добавлении комментария');
                  }
              } catch (error) {
                  console.error('Ошибка при отправке комментария:', error);
                  alert('Ошибка: ' + error.message);
              } finally {
                  // Восстанавливаем кнопку
                  submitBtn.innerHTML = originalText;
                  submitBtn.disabled = false;
              }
          });
      });
  });

  // Функция обновления комментариев
  async function updateComments(container, taskId) {
      const originalContent = container.innerHTML;
      container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>';

      try {
          const response = await fetch(`get_comments.php?task_id=${taskId}&t=${Date.now()}`);

          if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
          }

          const html = await response.text();
          container.innerHTML = html;
      } catch (error) {
          console.error('Ошибка загрузки комментариев:', error);
          container.innerHTML = originalContent;
      }
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
