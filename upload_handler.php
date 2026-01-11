<?php
session_start();

// Включите отладку временно
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверяем существование файла connection.php
if (!file_exists('connection.php')) {
    echo json_encode(['success' => false, 'error' => 'Файл connection.php не найден']);
    exit;
}

include "./connection.php";

// Настройки
$max_file_size = 5 * 1024 * 1024; // 5MB
$allowed_types = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    'application/pdf', 
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'application/zip',
    'application/x-rar-compressed'
];

header('Content-Type: application/json');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    exit;
}

// Проверяем загруженный файл
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error_msg = 'Неизвестная ошибка';
    switch ($_FILES['file']['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $error_msg = 'Файл слишком большой';
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_msg = 'Файл загружен не полностью';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_msg = 'Файл не выбран';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $error_msg = 'Отсутствует временная папка';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $error_msg = 'Ошибка записи на диск';
            break;
        case UPLOAD_ERR_EXTENSION:
            $error_msg = 'Расширение PHP остановило загрузку';
            break;
    }
    echo json_encode(['success' => false, 'error' => $error_msg]);
    exit;
}

$file = $_FILES['file'];
$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if ($task_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверный ID задачи']);
    exit;
}

// Проверка размера файла
if ($file['size'] > $max_file_size) {
    echo json_encode(['success' => false, 'error' => 'Файл слишком большой. Максимум 5MB']);
    exit;
}

// Проверка типа файла
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Недопустимый тип файла: ' . $file['type']]);
    exit;
}

// Проверяем доступ к задаче 
$check = $connection->query("SELECT 1 FROM tasks WHERE id = $task_id LIMIT 1");
if ($check->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Задача не найдена']);
    exit;
}

// Создаем директорию для загрузки
$upload_dir = 'uploads/tasks/' . $task_id . '/';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Не удалось создать директорию для загрузки']);
        exit;
    }
}

// Генерируем безопасное имя файла
$original_name = basename($file['name']);
$safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original_name);
$filename = uniqid() . '_' . time() . '_' . $safe_name;
$filepath = $upload_dir . $filename;

// Перемещаем файл
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Формируем публичный URL (уберите 'teaman/' если он не нужен)
    $public_url = '/' . $filepath;
    
    // Сохраняем в БД (если таблица существует)
    $file_id = null;
    try {
        // Проверяем существование таблицы uploaded_files
        $table_check = $connection->query("SHOW TABLES LIKE 'uploaded_files'");
        if ($table_check->num_rows > 0) {
            $stmt = $connection->prepare("INSERT INTO uploaded_files 
                (task_id, user_id, filename, original_name, file_path, file_type, file_size) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iissssi", $task_id, $user_id, $filename, $original_name, $filepath, $file['type'], $file['size']);
                if ($stmt->execute()) {
                    $file_id = $stmt->insert_id;
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        // Игнорируем ошибки БД, файл уже загружен
    }
    
    // Формируем ответ
    $response = [
        'success' => true,
        'file_id' => $file_id,
        'filename' => $filename,
        'original_name' => $original_name,
        'url' => $public_url,
        'file_size' => formatBytes($file['size']),
        'file_type' => $file['type'],
        'is_image' => strpos($file['type'], 'image/') === 0
    ];
    
    echo json_encode($response);
} else {
    error_log("Ошибка перемещения файла: " . $file['tmp_name'] . " -> " . $filepath);
    echo json_encode(['success' => false, 'error' => 'Ошибка перемещения файла. Проверьте права доступа.']);
}

// Функция форматирования размера
function formatBytes($bytes, $precision = 2) {
    if ($bytes <= 0) return '0 Bytes';
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>