<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['category_id'])) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    $stmt = $conn->prepare("
        UPDATE feedback 
        SET category_id = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$data['category_id'], $data['id']]);

    echo json_encode([
        'status' => 'success',
        'message' => 'อัพเดทประเภทเรียบร้อยแล้ว'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 