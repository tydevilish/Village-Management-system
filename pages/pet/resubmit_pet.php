<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// รับค่า JSON จาก request
$data = json_decode(file_get_contents('php://input'), true);
$petId = $data['pet_id'] ?? null;

try {
    // ตรวจสอบว่ามี pet_id
    if (!$petId) {
        throw new Exception('ไม่พบข้อมูลสัตว์เลี้ยง');
    }

    // ตรวจสอบว่าเป็นเจ้าของสัตว์เลี้ยงจริง
    $stmt = $conn->prepare("SELECT user_id FROM pets WHERE id = ?");
    $stmt->execute([$petId]);
    $pet = $stmt->fetch();

    if (!$pet || $pet['user_id'] !== $_SESSION['user_id']) {
        throw new Exception('คุณไม่มีสิทธิ์ดำเนินการนี้');
    }

    // อัพเดทสถานะเป็น pending
    $updateStmt = $conn->prepare("UPDATE pets SET status = 'pending',rejected_at = NULL ,rejected_by = NULL ,reject_reason = NULL WHERE id = ?");
    $updateStmt->execute([$petId]);

    echo json_encode([
        'status' => 'success',
        'message' => 'อัพเดทสถานะเรียบร้อยแล้ว'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
