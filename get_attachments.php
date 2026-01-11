<?php
session_start();
include "./connection.php";

if (!isset($_GET['comment_id'])) {
    exit;
}

$comment_id = (int)$_GET['comment_id'];

// Получаем вложения для комментария
$sql = "SELECT * FROM uploaded_files WHERE comment_id = ? ORDER BY uploaded_at";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<div class="comment-attachments">';
    echo '<small class="text-muted">Вложения:</small>';
    
    while ($file = $result->fetch_assoc()) {
        $is_image = in_array($file['file_type'], [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'
        ]);
        
        echo '<div class="attachment-item">';
        echo '<div class="attachment-icon">';
        
        if ($is_image) {
            echo '<i class="bi bi-image"></i>';
        } elseif (strpos($file['file_type'], 'pdf') !== false) {
            echo '<i class="bi bi-file-pdf"></i>';
        } elseif (strpos($file['file_type'], 'word') !== false) {
            echo '<i class="bi bi-file-word"></i>';
        } elseif (strpos($file['file_type'], 'excel') !== false) {
            echo '<i class="bi bi-file-excel"></i>';
        } elseif (strpos($file['file_type'], 'zip') !== false || strpos($file['file_type'], 'rar') !== false) {
            echo '<i class="bi bi-file-zip"></i>';
        } else {
            echo '<i class="bi bi-file-text"></i>';
        }
        
        echo '</div>';
        echo '<div class="attachment-info">';
        echo '<a href="' . htmlspecialchars($file['file_path']) . '" class="attachment-name" target="_blank">';
        echo htmlspecialchars($file['original_name']);
        echo '</a>';
        echo '<div class="attachment-size">';
        
        // Форматируем размер файла
        $size = $file['file_size'];
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        echo round($size, 2) . ' ' . $units[$i];
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
}

$stmt->close();
?>