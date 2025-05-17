<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if (!isset($_GET['pet_id'])) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT pv.*, v.name as vaccine_name 
        FROM pet_vaccines pv
        JOIN vaccines v ON pv.vaccine_id = v.id
        WHERE pv.pet_id = :pet_id
    ");
    
    $stmt->execute([':pet_id' => $_GET['pet_id']]);
    $vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($vaccines);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?> 