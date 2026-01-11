<?php
session_start();
include "./connection.php";

if (!isset($_GET['task_id'])) {
    exit;
}

$task_id = (int)$_GET['task_id'];

// Получаем комментарии
$sql_comments = "SELECT c.id, c.comment, u.first_name, u.last_name, c.created_at
                FROM task_comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.task_id = $task_id
                ORDER BY c.created_at ASC";

$comments_result = mysqli_query($connection, $sql_comments);

if ($comments_result !== false && mysqli_num_rows($comments_result) > 0) {
    while ($comment = mysqli_fetch_assoc($comments_result)) {
        ?>
        <div class="comment-item border-bottom pb-2 mb-2">
            <p class="mb-1"><strong><?= htmlspecialchars($comment['first_name']) ?> <?= htmlspecialchars($comment['last_name']) ?></strong>:</p>
            <div class="comment-content">
                <?=$comment['comment'] ?>
            </div>
            <small class="text-muted"><em><?= $comment['created_at'] ?></em></small>
        </div>
        <?php
    }
} else {
    echo '<p class="text-muted">Комментариев пока нет.</p>';
}

mysqli_free_result($comments_result);
?>