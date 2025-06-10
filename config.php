<?php
try {
    $conn = new PDO("mysql:host=localhost;dbname=abc_kantin;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?> 