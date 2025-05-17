<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['role_id'])) {
        throw new Exception('ไม่พบ ID ที่ต้องการลบ');
    }

    $roleId = $data['role_id'];

    // ตรวจสอบว่ามี users ที่ใช้ role นี้อยู่หรือไม่
    $stmt = $conn->prepare("SELECT COUNT(*) as user_count FROM users WHERE role_id = ?");
    $stmt->execute([$roleId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['user_count'] > 0) {
        throw new Exception('ไม่สามารถลบสิทธิ์นี้ได้ เนื่องจากมีสมาชิกกำลังใช้งานสิทธิ์นี้อยู่');
    }

    // ตรวจสอบว่ามี role นี้อยู่หรือไม่
    $stmt = $conn->prepare("SELECT role_id FROM roles WHERE role_id = ?");
    $stmt->execute([$roleId]);
    if (!$stmt->fetch()) {
        throw new Exception('ไม่พบข้อมูลสิทธิ์ที่ต้องการลบ');
    }

    $conn->beginTransaction();

    // ลบข้อมูลการอนุญาต
    $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$roleId]);

    // ลบข้อมูลสิทธิ์
    $stmt = $conn->prepare("DELETE FROM roles WHERE role_id = ?");
    $stmt->execute([$roleId]);

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'ลบข้อมูลสำเร็จ'
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 