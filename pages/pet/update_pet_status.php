<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

checkPageAccess(PAGE_MANAGE_PET);

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['pet_id']) || !isset($input['status'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ข้อมูลไม่ครบถ้วน'
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE pets 
        SET 
            status = :status,
            approved_at = :approved_at,
            approved_by = :approved_by,
            rejected_at = :rejected_at,
            rejected_by = :rejected_by,
            reject_reason = :reject_reason,
            updated_at = NOW()
        WHERE id = :pet_id
    ");
    
    $params = [
        ':status' => $input['status'],
        ':pet_id' => $input['pet_id'],
        ':approved_at' => null,
        ':approved_by' => null,
        ':rejected_at' => null,
        ':rejected_by' => null,
        ':reject_reason' => null
    ];

    if ($input['status'] === 'approved') {
        $params[':approved_at'] = date('Y-m-d H:i:s');
        $params[':approved_by'] = $_SESSION['user_id'];
    } elseif ($input['status'] === 'rejected') {
        $params[':rejected_at'] = date('Y-m-d H:i:s');
        $params[':rejected_by'] = $_SESSION['user_id'];
        $params[':reject_reason'] = $input['reject_reason'] ?? null;
    }
    
    $stmt->execute($params);

    // เพิ่มการแจ้งเตือน


    $title = $input['status'] === 'approved' ? 'อนุมัติข้อมูลสัตว์เลี้ยง' : 'ไม่อนุมัติข้อมูลสัตว์เลี้ยง';
    $message = $input['status'] === 'approved' 
        ? 'ข้อมูลสัตว์เลี้ยงของคุณได้รับการอนุมัติแล้ว' 
        : 'ข้อมูลสัตว์เลี้ยงของคุณไม่ได้รับการอนุมัติ' . 
          ($input['reject_reason'] ? ' เหตุผล: ' . $input['reject_reason'] : '');

    echo json_encode([
        'status' => 'success',
        'message' => 'อัพเดทสถานะเรียบร้อยแล้ว'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?>