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
    <?php
        $conn = mysqli_connect("localhost", "root", "", "teaman")
            or die("Ошибка подключения: " . mysqli_error($conn));

        if (!isset($_SESSION['user_id'])) {
          header("Location: index.php");
          exit;
        }
        $currentUserId = $_SESSION['user_id'];

        $sql = "
        SELECT t.*
        FROM teams t
        JOIN team_members tm ON t.id = tm.team_id
        WHERE tm.user_id = $currentUserId";

        $result = $conn->query($sql);
        $teams = $result->fetch_all(MYSQLI_ASSOC);



        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_team'])) {
          $code = trim($_POST['code']);

          if ($code === '') {
              $message = "Введите код команды";
          } else {
            $state = $conn->prepare("
                SELECT t.id, t.name,
                       EXISTS(SELECT 1 FROM team_members WHERE team_id = t.id AND user_id = ?) AS is_member
                FROM teams t
                WHERE t.access_code = ?");
            $state->bind_param("is", $currentUserId, $code);
            $state->execute();
            $result = $state->get_result();

            if ($row = $result->fetch_assoc()) {
                if ($row['is_member']) {
                    $message = "Вы уже состоите в команде «" . htmlspecialchars($row['name']) . "»";
                } else {
                    $insert = $conn->prepare("INSERT INTO team_members (team_id, user_id) VALUES (?, ?)");
                    $insert->bind_param("ii", $row['id'], $currentUserId);
                    $insert->execute();
                    $message = "Вы успешно вступили в команду «" . htmlspecialchars($row['name']) . "»";
                    header("Location: teams.php");
                    exit();
                }
            } else {
                $message = "Команда с таким кодом не найдена";
                  }
              }
          }

          if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_team'])) {
              $teamName = trim($_POST['team_name']);

              if ($teamName !== '') {

                  $code = strtoupper(bin2hex(random_bytes(4)));

                  $state = $conn->prepare("INSERT INTO teams (name, access_code) VALUES (?, ?)");
                  $state->bind_param("ss", $teamName, $code);
                  $state->execute();
                  $teamId = $conn->insert_id;

                  $insert = $conn->prepare("INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, 'owner')");
                  $insert->bind_param("ii", $teamId, $currentUserId);
                  $insert->execute();

                  $message = "Команда «" . htmlspecialchars($teamName) . "» создана";
                  header("Location: teams.php");
                  exit();
                  } else {
                  $message = "Укажите название команды";
              }
          }
?>

  <?php include('header.php') ?>

  <main class="d-flex flex-nowrap">

    <div class="container my-5">
      <h2>Мои команды</h2>
      <hr>

      <?php if (count($teams) > 0): ?>
        <div class="row row-cols-1 row-cols-md-3 g-3">
          <?php foreach ($teams as $t): ?>
            <?php
              $membersState = $conn->prepare("
                SELECT u.id, u.first_name, u.last_name, tm.role
                FROM users u
                JOIN team_members tm ON u.id = tm.user_id
                WHERE tm.team_id = ?");
              $membersState->bind_param("i", $t['id']);
              $membersState->execute();
              $membersResult = $membersState->get_result();
              $members = $membersResult->fetch_all(MYSQLI_ASSOC);

              $currentUserIsMember = false;
              foreach ($members as $m) {
                  if ($m['id'] == (int)$currentUserId) {
                      $currentUserIsMember = true;
                      break;
                  }
              }
              ?>

            <div class="col">
              <a href="" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#membersModal<?= $t['id'] ?>">
                <div class="card shadow-sm card_team_back">
                  <div class="card-body d-flex gap-3">
                    <h5 class="card-title card-team"><?= htmlspecialchars($t['name']) ?></h5>
                  </div>
                </div>
              </a>
            </div>

        <div class="modal fade" id="membersModal<?= $t['id'] ?>" tabindex="-1" aria-labelledby="membersModalLabel<?= $t['id'] ?>" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="membersModalLabel<?= $t['id'] ?>">Участники команды "<?= ($t['name']) ?>"</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
              </div>
              <div class="modal-body">
                <ul class="list-group">
                  <?php foreach ($members as $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?>
                      <?php
                        if ($m['role'] === 'owner') {
                            echo '<span class="badge" style="background-color: #86C232">Владелец</span>';
                        }
                      ?>
                    </li>
                  <?php endforeach; ?>
                </ul>

                <?php if ($currentUserIsMember): ?>
                    <p class="mt-3"><strong>Код команды:</strong> <?= htmlspecialchars($t['access_code']) ?></p>
                <?php endif; ?>
              </div>

            </div>
          </div>
        </div>
      <?php endforeach; ?>
        </div>
        <?php else: ?>
          <p>Вы пока не состоите ни в одной команде.</p>
        <?php endif; ?>

        <div class="mt-5">
          <h4>Создать новую команду</h4>
          <form method="POST" class="input-group">
            <input type="text" name="team_name" class="form-control" placeholder="Введите название команды" required>
            <button class="btn btn-primary" type="submit" name="create_team">Создать</button>
          </form>
        </div>

        <div class="mt-5">
          <h4>Вступить в команду по коду</h4>
          <form method="POST" class="input-group">
            <input type="text" name="code" class="form-control" placeholder="Введите код команды" required>
            <button class="btn btn-primary" name="join_team" type="submit">Вступить</button>
          </form>
      </div>

    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  </body>
</html>
