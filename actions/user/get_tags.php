<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_USERS);

try {
    $stmt = $conn->query("SELECT * FROM user_tags ORDER BY name");
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($tags);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 