<?php
// Отключаем вывод ошибок на экран для AJAX запросов
ini_set('display_errors', 0);
error_reporting(0);

session_start();
include "./connection.php";

// Проверяем подключение к БД
if ($connection->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к базе данных']);
    exit;
}

if (!function_exists('purify_html')) {
    function purify_html($html) {
        if (empty($html)) return '';
        
        // Разрешенные теги
        $allowed_tags = '<p><br><strong><b><em><i><u><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6>';
        
        // Базовая очистка
        $html = strip_tags($html, $allowed_tags);
        
        // Удаляем опасные атрибуты
        $html = preg_replace('/ on\w+="[^"]*"/i', '', $html);
        $html = preg_replace('/ javascript:/i', '', $html);
        
        return trim($html);
    }
}

header('Content-Type: application/json; charset=utf-8');

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

// Проверяем необходимые данные
if (!isset($_POST['action']) || $_POST['action'] !== 'add_comment') {
    echo json_encode(['success' => false, 'message' => 'Неверный запрос']);
    exit;
}

if (!isset($_POST['task_id']) || !isset($_POST['comment'])) {
    echo json_encode(['success' => false, 'message' => 'Отсутствуют необходимые данные']);
    exit;
}

$task_id = (int)$_POST['task_id'];
$user_id = (int)$_SESSION['user_id'];
$raw_comment = trim($_POST['comment']);

// Проверяем ID задачи
if ($task_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID задачи']);
    exit;
}

// Проверяем комментарий
if (empty($raw_comment)) {
    echo json_encode(['success' => false, 'message' => 'Комментарий не может быть пустым']);
    exit;
}

// Проверяем, не пустой ли комментарий после удаления тегов
$clean_text = strip_tags($raw_comment);
if (empty(trim($clean_text))) {
    echo json_encode(['success' => false, 'message' => 'Комментарий не может содержать только HTML теги']);
    exit;
}

// Очищаем HTML
$comment = purify_html($raw_comment);

// Проверяем доступ к задаче
$check_sql = "SELECT 1 FROM tasks t 
              LEFT JOIN project_teams pt ON t.project_id = pt.project_id 
              LEFT JOIN team_members tm ON pt.team_id = tm.team_id 
              LEFT JOIN projects p ON t.project_id = p.id
              WHERE t.id = ? 
              AND (t.author_id = ? OR t.executor_id = ? OR tm.user_id = ? OR p.owner_id = ?)
              LIMIT 1";

$check_stmt = $connection->prepare($check_sql);
$check_stmt->bind_param("iiiii", $task_id, $user_id, $user_id, $user_id, $user_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows == 0) {
    $check_stmt->close();
    echo json_encode(['success' => false, 'message' => 'Нет доступа к задаче']);
    exit;
}
$check_stmt->close();

// Добавляем комментарий в БД
$insert_sql = "INSERT INTO task_comments (task_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
$insert_stmt = $connection->prepare($insert_sql);

if (!$insert_stmt) {
    echo json_encode(['success' => false, 'message' => 'Ошибка подготовки запроса: ' . $connection->error]);
    exit;
}

$insert_stmt->bind_param("iis", $task_id, $user_id, $comment);

if ($insert_stmt->execute()) {
    $response = ['success' => true, 'message' => 'Комментарий добавлен'];
    
    // Добавляем ID нового комментария в ответ
    $new_comment_id = $insert_stmt->insert_id;
    $response['comment_id'] = $new_comment_id;
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $insert_stmt->error]);
}

$insert_stmt->close();
$connection->close();

if ($insert_stmt->execute()) {
    $new_comment_id = $insert_stmt->insert_id;
    
    // Обновляем прикрепленные файлы с ID комментария
    if (isset($_POST['file_ids']) && !empty($_POST['file_ids'])) {
        $file_ids = json_decode($_POST['file_ids'], true);
        if (is_array($file_ids) && !empty($file_ids)) {
            $file_ids_str = implode(',', array_map('intval', $file_ids));
            $update_files = $connection->query(
                "UPDATE uploaded_files SET comment_id = $new_comment_id 
                 WHERE id IN ($file_ids_str) AND task_id = $task_id"
            );
            
            // Обновляем комментарий, отмечаем что есть вложения
            $connection->query(
                "UPDATE task_comments SET has_attachments = TRUE 
                 WHERE id = $new_comment_id"
            );
        }
    }
    
    $response = ['success' => true, 'message' => 'Комментарий добавлен', 'comment_id' => $new_comment_id];
    echo json_encode($response);
}
?>