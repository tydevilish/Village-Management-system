<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบว่ามีการส่ง type_id มาหรือไม่
if (!isset($_GET['type_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่พบข้อมูล type_id'
    ]);
    exit;
}

try {
    // ดึงข้อมูลสายพันธุ์ตาม type_id
    $stmt = $conn->prepare("
        SELECT id, name 
        FROM species 
        WHERE type_id = :type_id 
        ORDER BY name
    ");
    
    $stmt->execute([':type_id' => $_GET['type_id']]);
    $species = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    header('Content-Type: application/json');
    echo json_encode($species);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
}
?> 