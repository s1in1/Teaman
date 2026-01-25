<?php
    $connection = new mysqli('mysql-8.4', 'root', '', 'teaman');
    if ($connection->connect_error) {
        die('DB connection error: '. $mysqli->connect_error);
    }
?>